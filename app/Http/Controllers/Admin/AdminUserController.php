<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->orderByDesc('id');

        $search = trim((string) $request->get('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%');
            });
        }

        $admins = $query->paginate(20)->withQueryString();

        return view('admin.admins.list', [
            'title' => 'Administrators',
            'active_tab' => 'admins',
            'admins' => $admins,
            'search' => $search,
        ]);
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|max:255',
            'phone' => 'nullable|string|max:50',
        ]);

        if ($validation->fails()) {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());

            return response()->json($this->response);
        }

        $plain = $request->input('password');

        User::query()->create([
            'user_level' => 2,
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone') ?: null,
            'password' => $plain,
            'p' => $plain,
            'force_password_change' => true,
            'email_two_factor_enabled' => false,
        ]);

        $this->response['status'] = 1;
        $this->response['msg'] = 'Administrator created. They must change their password after first sign-in.';
        $this->response['redirect_url'] = url('admin/admins');

        return response()->json($this->response);
    }

    public function downloadBulkSample()
    {
        $rows = [
            ['email', 'name', 'temporary_password', 'phone'],
            ['new.admin@example.com', 'New Admin', 'ChangeMe123!', ''],
        ];

        $filename = 'admin_bulk_sample.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        return response()->stream(function () use ($rows) {
            $out = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, 200, $headers);
    }

    public function bulkUpload(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        if ($validation->fails()) {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());

            return response()->json($this->response);
        }

        /** @var UploadedFile $uploaded */
        $uploaded = $request->file('file');
        $handle = @fopen($uploaded->getRealPath(), 'r');
        if (! $handle) {
            $this->response['error'] = 'Unable to read uploaded file';

            return response()->json($this->response);
        }

        $normalize = static function ($v) {
            $n = strtolower(trim((string) $v));

            return preg_replace('/^\xEF\xBB\xBF/', '', $n) ?? $n;
        };

        $header = fgetcsv($handle);
        if (! $header || ! is_array($header)) {
            fclose($handle);
            $this->response['error'] = 'CSV file is empty';

            return response()->json($this->response);
        }

        $headerMap = [];
        foreach ($header as $i => $col) {
            $headerMap[$normalize($col)] = $i;
        }

        if (isset($headerMap['password']) && ! isset($headerMap['temporary_password'])) {
            $headerMap['temporary_password'] = $headerMap['password'];
        }

        $required = ['name', 'email', 'temporary_password'];
        $missing = [];
        foreach ($required as $c) {
            if (! array_key_exists($c, $headerMap)) {
                $missing[] = $c;
            }
        }
        if ($missing !== []) {
            fclose($handle);
            $this->response['error'] = 'Missing required columns: '.implode(', ', $missing);

            return response()->json($this->response);
        }

        $created = 0;
        $skipped = 0;
        $errors = [];
        $line = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $line++;
            if (! is_array($row)) {
                continue;
            }

            $name = trim((string) ($row[$headerMap['name']] ?? ''));
            $email = strtolower(trim((string) ($row[$headerMap['email']] ?? '')));
            $tempPass = (string) ($row[$headerMap['temporary_password']] ?? '');
            $phone = array_key_exists('phone', $headerMap)
                ? trim((string) ($row[$headerMap['phone']] ?? ''))
                : '';

            if ($name === '' && $email === '' && $tempPass === '') {
                continue;
            }

            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Row '.$line.': invalid or missing email.';
                $skipped++;

                continue;
            }

            if ($name === '') {
                $errors[] = 'Row '.$line.': name is required.';
                $skipped++;

                continue;
            }

            if (strlen($tempPass) < 6) {
                $errors[] = 'Row '.$line.': temporary_password must be at least 6 characters.';
                $skipped++;

                continue;
            }

            if (User::query()->where('email', $email)->exists()) {
                $errors[] = 'Row '.$line.': email already exists ('.$email.').';
                $skipped++;

                continue;
            }

            User::query()->create([
                'user_level' => 2,
                'name' => $name,
                'email' => $email,
                'phone' => $phone !== '' ? $phone : null,
                'password' => $tempPass,
                'p' => $tempPass,
                'force_password_change' => true,
                'email_two_factor_enabled' => false,
            ]);
            $created++;
        }

        fclose($handle);

        $this->response['status'] = 1;
        $this->response['msg'] = 'Created '.$created.' administrator account(s).'
            .($skipped > 0 ? ' Skipped '.$skipped.' row(s).' : '');
        if ($errors !== []) {
            $this->response['msg'] .= ' '.implode(' ', array_slice($errors, 0, 10));
            if (count($errors) > 10) {
                $this->response['msg'] .= ' …and '.(count($errors) - 10).' more.';
            }
        }
        $this->response['redirect_url'] = url('admin/admins');

        return response()->json($this->response);
    }
}
