<p style="font-size:14px;font-family: arial,helvetica,sans-serif;">Om te eindigen het ondertekenen van de petitie, bevestig uw e-mailadres door te klikken op de link hieronder:</p>

<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td>
      <table border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td align="center" style="-webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px;" bgcolor="#941b80"><a href="{$url_confirm_and_keep}" target="_blank" style="font-size: 16px; font-family: Helvetica, Arial, sans-serif; color: #ffffff; text-decoration: none; color: #ffffff; text-decoration: none; -webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px; padding: 12px 18px; border: 1px solid #7e176d; display: inline-block;">Bevestig mijn ondertekening en houd me op de hoogte van andere campagnes</a></td>
        </tr>
      </table>
    </td>
  </tr>
</table>
<p style="font-size:14px;font-family: arial,helvetica,sans-serif;">Ook wanneer u liever geen verdere campagneberichten ontvangt, dient u uw ondertekening te <a href="{$url_confirm_and_not_receive}">bevestigen</a>.</p>
<script type="application/ld+json">
{ldelim}
  "@context": "http://schema.org",
  "@type": "EmailMessage",
  "potentialAction": {ldelim}
    "@type": "ConfirmAction",
    "name": "Confirm my signature",
    "handler": {ldelim}
      "@type": "HttpActionHandler",
      "url": "{$url_confirm_and_keep}-schema"
    {rdelim}
  {rdelim},
  "description": "Confirm my action and keep me updated on campaigns"
{rdelim}
</script>
