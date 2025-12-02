<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation à rejoindre {{ $invitation->company->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #007bff;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f8f9fa;
            padding: 30px;
            border: 1px solid #dee2e6;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0;">Invitation Chantix</h1>
    </div>
    
    <div class="content">
        <h2>Bonjour,</h2>
        
        <p>Vous avez été invité à rejoindre l'entreprise <strong>{{ $invitation->company->name }}</strong> sur la plateforme Chantix.</p>
        
        <p><strong>Rôle assigné :</strong> {{ ucfirst($invitation->role->name ?? 'Membre') }}</p>
        
        @if($invitation->message)
            <div style="background-color: #e7f3ff; padding: 15px; border-left: 4px solid #007bff; margin: 20px 0;">
                <p style="margin: 0;"><strong>Message :</strong></p>
                <p style="margin: 5px 0 0 0;">{{ $invitation->message }}</p>
            </div>
        @endif
        
        <p>Pour accepter cette invitation, cliquez sur le bouton ci-dessous :</p>
        
        <div style="text-align: center;">
            <a href="{{ route('invitations.accept', $invitation->token) }}" class="button">
                Accepter l'invitation
            </a>
        </div>
        
        <p style="font-size: 12px; color: #666; margin-top: 30px;">
            <strong>Note :</strong> Ce lien est valide jusqu'au {{ $invitation->expires_at->format('d/m/Y à H:i') }}.
            Si vous n'avez pas encore de compte, vous pourrez en créer un lors de l'acceptation.
        </p>
        
        <p style="font-size: 12px; color: #666;">
            Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :<br>
            <a href="{{ route('invitations.accept', $invitation->token) }}" style="color: #007bff; word-break: break-all;">
                {{ route('invitations.accept', $invitation->token) }}
            </a>
        </p>
    </div>
    
    <div class="footer">
        <p>Cet email a été envoyé par {{ $invitation->inviter->name ?? 'un administrateur' }} de {{ $invitation->company->name }}.</p>
        <p>© {{ date('Y') }} Chantix - Tous droits réservés</p>
    </div>
</body>
</html>

