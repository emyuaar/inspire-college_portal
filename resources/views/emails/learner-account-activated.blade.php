<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Your DirectSkills Learner Account Is Ready</title>
</head>

<body style="margin:0; padding:0; background:#f4f7fb; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">

    <div style="display:none; max-height:0; overflow:hidden; opacity:0;">
        Set your password and access your course after payment confirmation.
    </div>

    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f7fb; padding:30px 0;">
        <tr>
            <td align="center">

                <table width="600" cellpadding="0" cellspacing="0"
                    style="background:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 8px 30px rgba(0,0,0,0.08);">

                    <tr>
                        <td style="background:#01345B; padding:28px 32px; text-align:center;">
                            <img src="https://directskills.co.uk/images/DirectSkills_logo.webp" alt="DirectSkills"
                                style="max-width:180px; height:auto;">
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:34px 32px 20px 32px; text-align:center;">
                            <div
                                style="display:inline-block; background:#eaf8f3; color:#087f5b; padding:8px 16px; border-radius:999px; font-size:13px; font-weight:bold; margin-bottom:18px;">
                                Account Activated
                            </div>

                            <h1 style="margin:0; color:#01345B; font-size:26px; line-height:1.3;">
                                Your Learner Account Is Ready
                            </h1>

                            <p style="font-size:15px; line-height:1.7; color:#4b5563; margin:18px 0 0 0;">
                                Dear {{ $learner->first_name ?? 'Learner' }},
                                <br>
                                Your payment has been confirmed and your learner account has now been activated.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:10px 32px;">
                            <table width="100%" cellpadding="0" cellspacing="0"
                                style="background:#f8fafc; border:1px solid #e5e7eb; border-radius:14px;">
                                <tr>
                                    <td style="padding:22px;">
                                        <h2 style="margin:0 0 14px 0; color:#01345B; font-size:18px;">
                                            Course Details
                                        </h2>

                                        <p style="margin:0 0 8px 0; font-size:14px; color:#374151;">
                                            <strong>Course:</strong>
                                            {{ optional($enrolment->course)->title ?? optional($enrolment->course)->name ?? 'Your selected course' }}
                                        </p>

                                        <p style="margin:0; font-size:14px; color:#374151;">
                                            <strong>Login Email:</strong>
                                            {{ $learner->email_address ?? $learner->personal_email }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:28px 32px 10px 32px; text-align:center;">
                            <p style="font-size:15px; line-height:1.7; color:#4b5563; margin:0 0 22px 0;">
                                Please click the button below to set your password securely and access your learner
                                portal.
                            </p>

                            <a href="{{ $setupUrl }}"
                                style="display:inline-block; background:#A81A6A; color:#ffffff; text-decoration:none; padding:14px 30px; border-radius:10px; font-size:15px; font-weight:bold;">
                                Set My Password
                            </a>

                            <p style="font-size:12px; line-height:1.6; color:#6b7280; margin:20px 0 0 0;">
                                This secure setup link can be used once and remains valid until you set your password,
                                receive a newer setup email, or the link is revoked.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:24px 32px 34px 32px;">
                            <table width="100%" cellpadding="0" cellspacing="0"
                                style="background:#fff7fb; border-left:4px solid #A81A6A; border-radius:10px;">
                                <tr>
                                    <td style="padding:16px 18px;">
                                        <p style="margin:0; font-size:14px; line-height:1.6; color:#374151;">
                                            If you did not expect this email or need any help accessing your course,
                                            please contact the DirectSkills support team.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td
                            style="background:#f9fafb; padding:20px 32px; text-align:center; border-top:1px solid #e5e7eb;">
                            <p style="margin:0; font-size:13px; color:#6b7280;">
                                Thank you,<br>
                                <strong style="color:#01345B;">DirectSkills Team</strong>
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>

</html>
