<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $locale === 'en' ? 'Appointment Confirmation' : 'Confirmación de Cita' }}</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Arial, sans-serif; background: #f1f5f9; color: #1e293b; }
    .wrapper { max-width: 600px; margin: 40px auto; padding: 0 16px; }
    .card { background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
    .header { background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); padding: 40px 32px; text-align: center; }
    .header-icon { font-size: 48px; margin-bottom: 12px; }
    .header h1 { color: #ffffff; font-size: 24px; font-weight: 700; margin-bottom: 6px; }
    .header p { color: #bfdbfe; font-size: 14px; }
    .body { padding: 32px; }
    .greeting { font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 8px; }
    .intro { font-size: 14px; color: #64748b; margin-bottom: 28px; line-height: 1.6; }
    .appt-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; margin-bottom: 28px; }
    .appt-card-title { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #94a3b8; margin-bottom: 16px; }
    .appt-row { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 14px; }
    .appt-row:last-child { margin-bottom: 0; }
    .appt-icon { font-size: 20px; width: 28px; text-align: center; flex-shrink: 0; margin-top: 1px; }
    .appt-label { font-size: 12px; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 2px; }
    .appt-value { font-size: 15px; color: #1e293b; font-weight: 500; }
    .badge { display: inline-block; background: #dbeafe; color: #1e40af; border-radius: 20px; padding: 4px 12px; font-size: 13px; font-weight: 600; }
    .notes-box { background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 14px 16px; margin-bottom: 28px; }
    .notes-box p { font-size: 13px; color: #92400e; line-height: 1.6; }
    .notes-label { font-weight: 700; margin-bottom: 4px; }
    .footer { padding: 24px 32px; border-top: 1px solid #f1f5f9; text-align: center; }
    .footer p { font-size: 12px; color: #94a3b8; line-height: 1.6; }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="card">

      <!-- Header -->
      <div class="header">
        <div class="header-icon">📅</div>
        <h1>
          @if($recipientType === 'client')
            {{ $locale === 'en' ? 'Your appointment is confirmed!' : '¡Tu cita ha sido confirmada!' }}
          @else
            {{ $locale === 'en' ? 'New appointment scheduled' : 'Nueva cita agendada' }}
          @endif
        </h1>
        <p>
          @if($recipientType === 'client')
            {{ $locale === 'en'
              ? 'Your appointment with ' . $appointment->caseManager->name . ' has been successfully registered.'
              : 'Tu cita con ' . $appointment->caseManager->name . ' ha sido registrada exitosamente.' }}
          @else
            {{ $locale === 'en'
              ? 'A new appointment has been scheduled in your agenda.'
              : 'Se ha agendado una nueva cita en tu agenda.' }}
          @endif
        </p>
      </div>

      <!-- Body -->
      <div class="body">
        <p class="greeting">
          {{ $locale === 'en' ? 'Hello' : 'Hola' }}, {{ $recipient->name }} 👋
        </p>
        <p class="intro">
          @if($recipientType === 'client')
            {{ $locale === 'en'
              ? 'Below you will find the details of your appointment. Please make sure you are available at the indicated time.'
              : 'A continuación encontrarás los detalles de tu cita. Por favor asegúrate de estar disponible en el horario indicado.' }}
          @else
            {{ $locale === 'en'
              ? 'A new appointment has been scheduled with you. Below you will find the details.'
              : 'Se ha agendado una nueva cita contigo. A continuación encontrarás los detalles.' }}
          @endif
        </p>

        <!-- Detalles -->
        <div class="appt-card">
          <p class="appt-card-title">
            {{ $locale === 'en' ? 'Appointment Details' : 'Detalles de la cita' }}
          </p>

          <div class="appt-row">
            <span class="appt-icon">📋</span>
            <div>
              <p class="appt-label">{{ $locale === 'en' ? 'Reason' : 'Motivo' }}</p>
              <p class="appt-value">{{ $appointment->title }}</p>
            </div>
          </div>

          <div class="appt-row">
            <span class="appt-icon">📅</span>
            <div>
              <p class="appt-label">{{ $locale === 'en' ? 'Date' : 'Fecha' }}</p>
              <p class="appt-value">
                @if($locale === 'en')
                  {{ \Carbon\Carbon::parse($appointment->appointment_date)->locale('en')->translatedFormat('l, F d, Y') }}
                @else
                  {{ \Carbon\Carbon::parse($appointment->appointment_date)->locale('es')->translatedFormat('l, d \d\e F \d\e Y') }}
                @endif
              </p>
            </div>
          </div>

          <div class="appt-row">
            <span class="appt-icon">⏰</span>
            <div>
              <p class="appt-label">{{ $locale === 'en' ? 'Schedule' : 'Horario' }}</p>
              <p class="appt-value">
                <span class="badge">
                  {{ substr($appointment->start_time, 0, 5) }} – {{ substr($appointment->end_time, 0, 5) }}
                </span>
              </p>
            </div>
          </div>

          <div class="appt-row">
            <span class="appt-icon">👤</span>
            <div>
              <p class="appt-label">Case Manager</p>
              <p class="appt-value">{{ $appointment->caseManager->name }}</p>
            </div>
          </div>

          <div class="appt-row">
            <span class="appt-icon">🧑</span>
            <div>
              <p class="appt-label">{{ $locale === 'en' ? 'Client' : 'Cliente' }}</p>
              <p class="appt-value">{{ $appointment->client->name }}</p>
            </div>
          </div>
        </div>

        <!-- Notas -->
        @if($appointment->notes)
        <div class="notes-box">
          <p class="notes-label">📝 {{ $locale === 'en' ? 'Additional notes' : 'Notas adicionales' }}</p>
          <p>{{ $appointment->notes }}</p>
        </div>
        @endif

      </div>

      <!-- Footer -->
      <div class="footer">
        <p>
          {{ $locale === 'en'
            ? 'This is an automated message, please do not reply to this email.'
            : 'Este es un mensaje automático, por favor no respondas a este correo.' }}
        </p>
        <p style="margin-top: 8px;">© {{ date('Y') }} {{ $locale === 'en' ? 'Appointment System' : 'Sistema de Citas' }}. {{ $locale === 'en' ? 'All rights reserved.' : 'Todos los derechos reservados.' }}</p>
      </div>

    </div>
  </div>
</body>
</html>