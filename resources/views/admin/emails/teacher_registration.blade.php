<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px;">
        <h2 style="color: #4CAF50; margin-top: 0;">Registration Successful!</h2>
        
        <p>Dear User,</p>
        
        <p>Your account has been successfully registered in our system.</p>
        
        <div style="background-color: #ffffff; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #4CAF50;">
            <p style="margin: 0;"><strong>Your Login Credentials:</strong></p>
            <p style="margin: 10px 0 0 0;"><strong>Name:</strong> {{ $name }}</p>
            @if($email)
            <p style="margin: 10px 0 0 0;"><strong>Email:</strong> {{ $email }}</p>
            @endif
            @if($phone)
            <p style="margin: 10px 0 0 0;"><strong>Phone:</strong> {{ $phone }}</p>
            @endif
            <p style="margin: 10px 0 0 0;"><strong>Password:</strong> {{ $password }}</p>
        </div>
        
        <p><strong>Important:</strong> Please keep your password secure and change it after your first login.</p>
        
        <p>You can now log in to your account using your email or phone number along with the password provided above.</p>
        
        <p style="margin-top: 30px;">Best regards,<br>Admin Team</p>
    </div>
</body>
</html>
