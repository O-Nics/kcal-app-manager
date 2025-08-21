<!DOCTYPE html>
<html lang="fr" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no">
    <title>@yield('title', config('app.name'))</title>

    <!-- Email-safe, simple layout -->
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:AllowPNG/>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->

    <style>
        body { margin:0; padding:0; }
        table { border-collapse:collapse; }
        img { border:0; display:block; line-height:100%; }
        @media only screen and (max-width:600px) { .container { width:100% !important; } .px { padding-left:16px !important; padding-right:16px !important; } }
    </style>
    @yield('style')
</head>
<body style="margin:0; padding:0; background:#f5f7fb;">
<!-- Preheader -->
<div style="display:none; max-height:0; overflow:hidden; mso-hide:all; opacity:0;">
    @yield('preheader', 'Programme kcalorique — ' . config('app.name'))
</div>

<table role="presentation" width="100%" style="background:#f5f7fb;" cellpadding="0" cellspacing="0">
    <tr>
        <td align="center" style="padding:24px 8px;">
            <table role="presentation" width="600" class="container" style="width:600px; max-width:600px; background:#ffffff; border-radius:0; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.06);" cellpadding="0" cellspacing="0">
                <tr>
                    <td align="center" style="background:#0E7C86; padding:16px;">
                        <a href="{{ config('app.url') }}" target="_blank" style="text-decoration:none;">
                            <img src="@yield('logo', 'https://dummyimage.com/160x40/ffffff/0E7C86&text=' . urlencode(config('app.name')))" width="160" height="40" alt="{{ config('app.name') }}" style="height:auto;">
                        </a>
                    </td>
                </tr>
                <tr>
                    <td class="px" style="padding:24px 24px 0; text-align:center;">
                        <h1 style="margin:0; font-family:Arial,Helvetica,sans-serif; font-size:22px; line-height:1.3; color:#0B6169;">@yield('title')</h1>
                        @hasSection('subtitle')
                            <div style="height:8px; line-height:8px; font-size:0;"></div>
                            <p style="margin:0; font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#6B7280;">@yield('subtitle')</p>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="px" style="padding:16px 24px 8px;">
                        <div style="font-family:Arial,Helvetica,sans-serif; font-size:15px; line-height:1.6; color:#1F2937; text-align:left;">
                            @yield('content')
                        </div>
                    </td>
                </tr>
                @hasSection('action_url')
                    <tr>
                        <td align="center" style="padding:8px 24px 20px;">
                            <!--[if mso]>
              <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="@yield('action_url')" style="height:44px;v-text-anchor:middle;width:260px;" arcsize="10%" stroke="f" fillcolor="#0E7C86">
                <w:anchorlock/>
                <center style="color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:bold;">@yield('action_text','Voir mon programme')</center>
              </v:roundrect>
              <![endif]-->
                            <a href="@yield('action_url')" target="_blank" style="background:#0E7C86; color:#ffffff; display:inline-block; padding:12px 22px; border-radius:0px; font-family:Arial,Helvetica,sans-serif; font-size:15px; font-weight:bold; text-decoration:none;">
                                @yield('action_text','Voir mon programme')
                            </a>
                        </td>
                    </tr>
                <tr>
                    <td align="center" style="padding:0 24px 24px;">
                        <p style="font-family:Arial,Helvetica,sans-serif; font-size:10px; color:#6B7280; margin:0; text-align:center;">
                            Si le bouton ne fonctionne pas, copiez/collez ce lien dans votre navigateur :<br>
                            <a href="@yield('action_url')" style="color:#0E7C86; word-break:break-all;">
                                @yield('action_url')
                            </a>
                        </p>
                    </td>
                </tr>
                @endif
                <tr>
                    <td style="background:#F2F5F9; padding:16px 24px; text-align:center;">
                        <p style="margin:0; font-family:Arial,Helvetica,sans-serif; font-size:12px; color:#6B7280;">Email automatique envoyé par <strong>{{ config('app.name') }}</strong>. Merci de ne pas répondre.</p>
                        <div style="height:8px; line-height:8px; font-size:0;"></div>
                        <p style="margin:0; font-family:Arial,Helvetica,sans-serif; font-size:12px; color:#6B7280;">Besoin d'aide ? <a href="mailto:{{ config('mail.from.address') }}" style="color:#0B6169; text-decoration:underline;">{{ config('mail.from.address') }}</a></p>
                        <div style="height:8px; line-height:8px; font-size:0;"></div>
                        <p style="margin:0; font-family:Arial,Helvetica,sans-serif; font-size:12px; color:#6B7280;">© {{ date('Y') }} {{ config('app.name') }}</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
