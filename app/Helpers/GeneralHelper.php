<?php

use App\Models\Currency;
use App\Models\PostCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Category;
use App\Models\WhatsappNotification;

function formatErrors($errors = [])
{
    $error_array = [];

    if (count($errors) > 0) {
        foreach ($errors as $k => $v) {
            if (count(explode('.', $k)) > 1) {
                $nk = explode('.', $k)[0] . '[' . explode('.', $k)[1] . ']';
                $error_array[$nk] = $v[0];
            } else {
                $error_array[$k] = $v[0];
            }
        }
    }

    return $error_array;
}

if (!function_exists('toSqlWithBindings')) {
    function toSqlWithBindings($query)
    {
        $bindings = $query->getBindings();
        $sql = $query->toSql();

        foreach ($bindings as $binding) {
            $value = is_numeric($binding) ? $binding : "'$binding'";
            $sql = preg_replace('/\?/', $value, $sql, 1);
        }

        return $sql;
    }
}

if (!function_exists('createSlug')) {
    function createSlug($id = '', $name = '', $table_name = '')
    {
        $count = 0;
        $name = Str::slug($name);
        $slug_name = $name; // Create temp name
        while (true) {
            $result = DB::table($table_name)
                ->select('id')
                ->when($id, function ($q) use ($id) {
                    $q->where('id', '!=', $id);
                })
                ->where('slug', $slug_name)
                ->count();

            if ($result == 0) {
                break;
            }

            $slug_name = $name . '-' . ++$count;
        }
        return $slug_name; // Return temp name
    }
}

function pagination($total, $per_page = 10, $page = 1, $url = '?')
{
    $adjacents = '2';
    $page = $page == 0 ? 1 : $page;
    $start = ($page - 1) * $per_page;
    $prev = $page - 1;
    $next = $page + 1;
    $lastpage = ceil($total / $per_page);
    $lpm1 = $lastpage - 1;
    $pagination = '';

    $arrowStyle = "font-size:1.25rem;line-height:1;font-weight:600;";
    $prevArrow = "<span style='{$arrowStyle}'>&lsaquo;</span>";
    $nextArrow = "<span style='{$arrowStyle}'>&rsaquo;</span>";

    if ($lastpage > 1) {
        $pagination .= '<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">';
        $pagination .= "<ul class='pagination pagination-borderless justify-content-end mb-0'>";
        if ($lastpage < 7 + $adjacents * 2) {
            if ($page > 1) {
                $pagination .= "<li class='page-item prev'><a href='{$url}page=$prev' class='page-link'>$prevArrow</a></li>";
            } else {
                $pagination .= "<li class='page-item prev disabled'><span class='page-link'>$prevArrow</span></li>";
            }

            for ($counter = 1; $counter <= $lastpage; $counter++) {
                if ($counter == $page) {
                    $pagination .= "<li class='page-item active'><a class='page-link'>$counter</a></li>";
                } else {
                    $pagination .= "<li class='page-item'><a href='{$url}page=$counter' class='page-link'>$counter</a></li>";
                }
            }

            if ($next <= $lastpage) {
                $pagination .= "<li class='page-item next'><a href='{$url}page=$next' class='page-link'>$nextArrow</a></li>";
            } else {
                $pagination .= "<li class='page-item next disabled'><span class='page-link'>$nextArrow</span></li>";
            }
        } elseif ($lastpage > 5 + $adjacents * 2) {
            $pagination .= "<li class='page-item prev'><a href='{$url}page=$prev' class='page-link'>$prevArrow</a></li>";

            for ($counter = 1; $counter < 4 + $adjacents * 2; $counter++) {
                if ($counter == $page) {
                    $pagination .= "<li class='page-item active'><a class='page-link'>$counter</a></li>";
                } else {
                    $pagination .= "<li class='page-item'><a href='{$url}page=$counter' class='page-link'>$counter</a></li>";
                }
            }

            $pagination .= "<li class='page-item disabled'><span class='page-link'>...</span></li>";
            $pagination .= "<li class='page-item'><a href='{$url}page=$lpm1' class='page-link'>$lpm1</a></li>";
            $pagination .= "<li class='page-item'><a href='{$url}page=$lastpage' class='page-link'>$lastpage</a></li>";

            if ($next <= $lastpage) {
                $pagination .= "<li class='page-item next'><a href='{$url}page=$next' class='page-link'>$nextArrow</a></li>";
            } else {
                $pagination .= "<li class='page-item next disabled'><span class='page-link'>$nextArrow</span></li>";
            }
        } else {
            if ($page > 1) {
                $pagination .= "<li class='page-item prev'><a href='{$url}page=$prev' class='page-link'>$prevArrow</a></li>";
            } else {
                $pagination .= "<li class='page-item prev disabled'><span class='page-link'>$prevArrow</span></li>";
            }

            $pagination .= "<li class='page-item'><a href='{$url}page=1' class='page-link'>1</a></li>";
            $pagination .= "<li class='page-item'><a href='{$url}page=2' class='page-link'>2</a></li>";
            $pagination .= "<li class='page-item disabled'><span class='page-link'>...</span></li>";

            for ($counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++) {
                if ($counter == $page) {
                    $pagination .= "<li class='page-item active'><a class='page-link'>$counter</a></li>";
                } else {
                    $pagination .= "<li class='page-item'><a href='{$url}page=$counter' class='page-link'>$counter</a></li>";
                }
            }

            $pagination .= "<li class='page-item disabled'><span class='page-link'>...</span></li>";
            $pagination .= "<li class='page-item'><a href='{$url}page=$lpm1' class='page-link'>$lpm1</a></li>";
            $pagination .= "<li class='page-item'><a href='{$url}page=$lastpage' class='page-link'>$lastpage</a></li>";
        }

        $pagination .= '</ul>';
        $pagination .= '</div>';
    }
    return $pagination;
}



// function contactus()
// {
//     $contact = ContactUs::find(1);
//     return $contact;
// }

if (!function_exists('portal_same_origin_path')) {
    /**
     * URL path (leading slash) for same-origin AJAX, including subdirectory prefix from the current request.
     * Avoids posting to /user/... when the app is under /elitegrade (wrong route, no session cookie → 401).
     */
    function portal_same_origin_path(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        $req = parse_url(request()->getRequestUri(), PHP_URL_PATH);
        $req = is_string($req) && $req !== '' ? $req : '/';

        $markers = [
            '/user/teacher/',
            '/user/student/',
            '/user/parent/',
            '/user/select-teacher',
            '/user/select-student',
            '/user/profile',
            '/user/security',
            '/user/dashboard',
            '/user/logout',
            '/user/',
        ];
        foreach ($markers as $m) {
            $p = strpos($req, $m);
            if ($p !== false) {
                $prefix = substr($req, 0, $p);

                return ($prefix === '' ? '' : rtrim($prefix, '/')) . $path;
            }
        }

        $fromUrl = parse_url(url(ltrim($path, '/')), PHP_URL_PATH);

        return is_string($fromUrl) && $fromUrl !== '' ? $fromUrl : $path;
    }
}
