<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $locale === 'en' ? 'Appointment Status Update' : 'Actualización de Estado de Cita' }}</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Arial, sans-serif; background: #f1f5f9; color: #1e293b; }
    .wrapper { max-width: 600px; margin: 40px auto; padding: 0 16px; }
    .card { background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
    .header { padding: 40px 32px; text-align: center; }
    .header-icon { font-size: 48px; margin-bottom: 12px; }
    .header h1 { color: #ffffff; font-size: 24px; font-weight: 700; margin-bottom: 6px; }
    .header p { color: rgba(255,255,255,0.8); font-size: 14px; }
    .header-confirmed  { background: linear-gradient(135deg, #15803d 0%, #22c55e 100%); }
    .header-completed  { background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); }
    .header-cancelled  { background: linear-gradient(135deg, #b91c1c 0%, #ef4444 100%); }
    .header-no_show    { background: linear-gradient(135deg, #92400e 0%, #f59e0b 100%); }
    .header-default    { background: linear-gradient(135deg, #475569 0%, #94a3b8 100%); }
    .body { padding: 32px; }
    .greeting { font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 8px; }
    .intro { font-size: 14px; color: #64748b; margin-bottom: 28px; line-height: 1.6; }
    .status-banner { border-radius: 12px; padding: 20px 24px; margin-bottom: 28px; text-align: center; }
    .status-banner-confirmed { background: #dcfce7; border: 1px solid #86efac; }
    .status-banner-completed  { background: #dbeafe; border: 1px solid #93c5fd; }
    .status-banner-cancelled  { background: #fee2e2; border: 1px solid #fca5a5; }
    .status-banner-no_show    { background: #fef3c7; border: 1px solid #fcd34d; }
    .status-banner-default    { background: #f1f5f9; border: 1px solid #cbd5e1; }
    .status-label { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
    .status-label-confirmed { color: #15803d; }
    .status-label-completed  { color: #1e40af; }
    .status-label-cancelled  { color: #b91c1c; }
    .status-label-no_show    { color: #92400e; }
    .status-label-default    { color: #475569; }
    .status-value { font-size: 22px; font-weight: 800; }
    .status-value-confirmed { color: #15803d; }
    .status-value-completed  { color: #1e40af; }
    .status-value-cancelled  { color: #b91c1c; }
    .status-value-no_show    { color: #92400e; }
    .status-value-default    { color: #475569; }
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
<?php
  $status = $appointment->status;
  $statusClass = in_array($status, ['confirmed','completed','cancelled','no_show']) ? $status : 'default';

  $statusEmoji = match($status) {
    'confirmed' => '✅',
    'completed' => '🎉',
    'cancelled' => '❌',
    'no_show'   => '⚠️',
    default     => '🔔',
  };

  $statusLabel = match($status) {
    'confirmed' => ($locale === 'en' ? 'Confirmed'    : 'Confirmada'),
    'completed' => ($locale === 'en' ? 'Completed'    : 'Completada'),
    'cancelled' => ($locale === 'en' ? 'Cancelled'    : 'Cancelada'),
    'no_show'   => ($locale === 'en' ? 'No Show'      : 'No se presentó'),
    default     => ($locale === 'en' ? 'Updated'      : 'Actualizada'),
  };

  $headerTitle = match($status) {
    'confirmed' => ($locale === 'en' ? 'Your appointment has been confirmed'     : 'Tu cita ha sido confirmada'),
    'completed' => ($locale === 'en' ? 'Your appointment has been completed'     : 'Tu cita ha sido completada'),
    'cancelled' => ($locale === 'en' ? 'Your appointment has been cancelled'     : 'Tu cita ha sido cancelada'),
    'no_show'   => ($locale === 'en' ? 'Missed appointment'                      : 'Cita no atendida'),
    default     => ($locale === 'en' ? 'Appointment status updated'              : 'Estado de cita actualizado'),
  };
?>
  <div class="wrapper">
    <div class="card">

      <!-- Header -->
      <div class="header header-{{ $statusClass }}">
        <div class="header-icon">{{ $statusEmoji }}</div>
        <h1>{{ $headerTitle }}</h1>
        <p>
          {{ $locale === 'en'
            ? 'The status of the appointment "' . $appointment->title . '" has been updated.'
            : 'El estado de la cita "' . $appointment->title . '" ha sido actualizado.' }}
        </p>
      </div>

      <!-- Body -->
      <div class="body">
        <p class="greeting">
          {{ $locale === 'en' ? 'Hello' : 'Hola' }},
          {{ $recipient === 'client' ? $appointment->client?->name : $appointment->caseManager?->name }} 👋
        </p>
        <p class="intro">
          @if($recipient === 'client')
            {{ $locale === 'en'
              ? 'We want to let you know that the status of your appointment has changed. Please review the details below.'
              : 'Te informamos que el estado de tu cita ha cambiado. Por favor revisa los detalles a continuación.' }}
          @else
            {{ $locale === 'en'
              ? 'The status of one of your scheduled appointments has been updated. Please review the details below.'
              : 'El estado de una de tus citas agendadas ha sido actualizado. Por favor revisa los detalles a continuación.' }}
          @endif
        </p>

        <!-- Banner de estado -->
        <div class="status-banner status-banner-{{ $statusClass }}">
          <p class="status-label status-label-{{ $statusClass }}">
            {{ $locale === 'en' ? 'New status' : 'Nuevo estado' }}
          </p>
          <p class="status-value status-value-{{ $statusClass }}">
            {{ $statusEmoji }} {{ $statusLabel }}
          </p>
        </div>

        <!-- Detalles de la cita -->
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
              <p class="appt-value">{{ $appointment->caseManager?->name }}</p>
            </div>
          </div>

          <div class="appt-row">
            <span class="appt-icon">🧑</span>
            <div>
              <p class="appt-label">{{ $locale === 'en' ? 'Client' : 'Cliente' }}</p>
              <p class="appt-value">{{ $appointment->client?->name }}</p>
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