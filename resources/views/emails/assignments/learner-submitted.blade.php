<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignment Submitted Successfully</title>
</head>

<body
    style="margin: 0; padding: 0; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f7f9; color: #333333; -webkit-font-smoothing: antialiased;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
        style="background-color: #f4f7f9; padding: 30px 15px;">
        <tr>
            <td align="center">
                <!-- Main Container -->
                <table role="presentation" width="600" cellspacing="0" cellpadding="0"
                    style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); max-width: 600px; width: 100%;">
                    <!-- Header -->
                    <tr>
                        <td align="center"
                            style="background-color: #01345B; padding: 30px 20px; border-bottom: 4px solid #d63384;">
                            <img src="https://directskills.co.uk/images/DirectSKills%20inverted-02.png"
                                alt="DirectSkills Logo" width="220"
                                style="display: block; max-width: 220px; height: auto;">
                        </td>
                    </tr>

                    <!-- Title Area -->
                    <tr>
                        <td style="padding: 35px 35px 20px 35px; text-align: center;">
                            <h1 style="margin: 0; font-size: 26px; font-weight: 700; color: #01345B;">Assignment
                                Submitted Successfully</h1>
                            <p style="margin: 10px 0 0 0; font-size: 16px; color: #666666;">Your submission has been
                                securely received by our system.</p>
                        </td>
                    </tr>

                    <!-- Content Area -->
                    <tr>
                        <td style="padding: 15px 35px 30px 35px;">
                            <p style="font-size: 16px; line-height: 24px; color: #333333; margin: 0 0 20px 0;">
                                Dear <strong>{{ $learnerName ?? 'Learner' }}</strong>,
                            </p>
                            <p style="font-size: 16px; line-height: 24px; color: #333333; margin: 0 0 25px 0;">
                                Thank you for submitting your assignment. We have successfully recorded your files and
                                submission details in the portal.
                            </p>

                            <!-- Submission Card -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 25px;">
                                <tr>
                                    <td style="padding: 20px 25px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td style="padding: 8px 0; border-bottom: 1px solid #edf2f7; font-size: 15px; color: #64748b;"
                                                    width="35%"><strong>Course:</strong></td>
                                                <td
                                                    style="padding: 8px 0; border-bottom: 1px solid #edf2f7; font-size: 15px; color: #0f172a; font-weight: 500;">
                                                    {{ $courseName ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <td
                                                    style="padding: 8px 0; border-bottom: 1px solid #edf2f7; font-size: 15px; color: #64748b;">
                                                    <strong>Assignment:</strong></td>
                                                <td
                                                    style="padding: 8px 0; border-bottom: 1px solid #edf2f7; font-size: 15px; color: #0f172a; font-weight: 500;">
                                                    {{ $assignmentName ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <td
                                                    style="padding: 8px 0; border-bottom: 1px solid #edf2f7; font-size: 15px; color: #64748b;">
                                                    <strong>File Submitted:</strong></td>
                                                <td
                                                    style="padding: 8px 0; border-bottom: 1px solid #edf2f7; font-size: 15px; color: #01345B; font-weight: 600; word-break: break-all;">
                                                    {{ $fileName ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <td
                                                    style="padding: 8px 0; border-bottom: 1px solid #edf2f7; font-size: 15px; color: #64748b;">
                                                    <strong>Submitted On:</strong></td>
                                                <td
                                                    style="padding: 8px 0; border-bottom: 1px solid #edf2f7; font-size: 15px; color: #0f172a; font-weight: 500;">
                                                    {{ $submittedAt ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0; font-size: 15px; color: #64748b;">
                                                    <strong>Attempt:</strong></td>
                                                <td
                                                    style="padding: 8px 0; font-size: 15px; color: #0f172a; font-weight: 500;">
                                                    {{ $attemptNumber ?? '1' }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <p style="font-size: 16px; line-height: 24px; color: #333333; margin: 0 0 25px 0;">
                                Your submission is now with our assessment team for review. You will be notified via
                                email once your assessment has been evaluated and graded.
                            </p>

                            <p style="font-size: 16px; line-height: 24px; color: #333333; margin: 0;">
                                Kind regards,<br>
                                <strong>Portal DirectSkills</strong>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td
                            style="background-color: #01345B; padding: 25px 30px; text-align: center; border-top: 1px solid #104b77;">
                            <p
                                style="margin: 0 0 10px 0; font-size: 16px; font-weight: 700; color: #ffffff; letter-spacing: 0.5px;">
                                DirectSkills</p>
                            <p style="margin: 0 0 5px 0; font-size: 13px; color: #cbd5e1;">This is an automated
                                notification from the DirectSkills Assessment System.</p>
                            <p style="margin: 0; font-size: 13px; color: #94a3b8;">Please do not reply directly to this
                                email.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>