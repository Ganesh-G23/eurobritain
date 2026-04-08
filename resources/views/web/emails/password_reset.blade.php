<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset your password</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px;">
        <h2 style="color: #333; margin-top: 0;">Password reset</h2>

        <p>Hello{{ isset($user->name) ? ' '.$user->name : '' }},</p>

        <p>You requested a password reset for your EliteGrade account. Click the button below to choose a new password. This link will expire after a limited time.</p>

        <p style="margin: 24px 0;">
            <a href="{{ $resetUrl }}" style="display: inline-block; background-color: #4CAF50; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 5px;">Reset password</a>
        </p>

        <p style="word-break: break-all; font-size: 14px; color: #666;">If the button does not work, copy and paste this link into your browser:<br>{{ $resetUrl }}</p>

        <p style="margin-top: 24px;">If you did not request this, you can ignore this email.</p>

        <p style="margin-top: 30px;">Best regards,<br>{{ config('app.name') }}</p>
    </div>
</body>
</html>
