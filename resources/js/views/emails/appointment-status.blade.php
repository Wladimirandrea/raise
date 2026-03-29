<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $locale === 'en' ? 'Appointment Status Updated' : 'Estado de Cita Actualizado' }}</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Arial, sans-serif; background: #f1f5f9; color: #1e293b; }
    .wrapper { max-width: 600px; margin: 40px auto; padding: 0 16px; }
    .card { background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
    .header { padding: 40px 32px; text-align: center; }
    .header-icon { font-size: 48px; margin-bottom: 12px; }
    .header h1 { font-size: 22px; font-weight: 700; margin-bottom: 6px; }
    .header p { font-size: 14px; }
    .body { padding: 32px; }
    .greeting { font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 8px; }
    .intro { font-size: 14px; color: #64748b; margin-bottom: 24px; line-height: 1.6; }
    .status-box { border-radius: 12px; padding: 20px 24px; margin-bottom: 24px; text-align: center; }
    .status-label { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 8px; }
    .status-value { font-size: 22px; font-weight: 700; }
    .appt-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 24px; }
    .appt-row { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 12px; }
    .appt-row:last-child { margin-bottom: 0; }
    .appt-icon { font-size: 18px; width: 24px; text-align: center; flex-shrink: 0; }
    .appt-label { font-size: 11px; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; margin-bottom: 2px; }
    .appt-value { font-size: 14px; color: #1e293b; font-weight: 500; }
    .badge { display: inline-block; border-radius: 20px; padding: 4px 12px; font-size: 13px; font-weight: 600; }
    .footer { padding: 24px 32px; border-top: 1px solid #f1f5f9; text-align: center; }
    .footer p { font-size: 12px; color: #94a3b8; line-height: 1.6; }

    /* Status colors */
    .status-pending   { background: #fef3c7; color: #92400e; }
    .status-confirmed { background: #dbeafe; color: #1e40af; }
    .status-completed { background: #d1fae5; color: #065f46; }
    .status-cancelled { background: #fee2e2; color: #991b1b; }
    .status-no_show   { background: #f1f5f9; color: #475569; }

    .header-pending   { background: #fef3c7; }
    .header-confirmed { background: #dbeafe; }
    .header-completed { background: #d1fae5; }
    .header-cancelled { background: #fee2e2; }
    .header-no_show   { background: #f1f5f9; }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="card">

      <!-- Header con color según estado -->
      <div class="header header-{{ $appointment->status }}">
        <div class="header-icon">
          @php
            $icons = ['pending' => '⏳', 'confirmed' => '✅', 'completed' => '🎉', 'cancelled' => '❌', 'no_show' => '👻'];
            echo $icons[$appointment->status] ?? '📅';
          @endphp
        </div>
        <h1>
          @if($locale === 'en')
            Appointment Status Updated
          @else
            Estado de Cita Actualizado
          @endif
        </h1>
        <p>
          @if($locale === 'en')
            The status of your appointment has changed.
          @else
            El estado de tu cita ha cambiado.
          @endif
        </p>
      </div>

      <!-- Body -->
      <div class="body">
        <p class="greeting">
          {{ $locale === 'en' ? 'Hello' : 'Hola' }}, {{ $appointment->caseManager->name }} 👋
        </p>
        <p class="intro">
          @if($locale === 'en')
            The following appointment has been updated with a new status.
          @else
            La siguiente cita ha sido actualizada con un nuevo estado.
          @endif
        </p>

        <!-- Nuevo estado -->
        <div class="status-box status-{{ $appointment->status }}">
          <p class="status-label">{{ $locale === 'en' ? 'New Status' : 'Nuevo Estado' }}</p>
          <p class="status-value">
            @php
              $statusLabels = [
                'pending'   => ['es' => 'Pendiente',       'en' => 'Pending'],
                'confirmed' => ['es' => 'Confirmada',      'en' => 'Confirmed'],
                'completed' => ['es' => 'Completada',      'en' => 'Completed'],
                'cancelled' => ['es' => 'Cancelada',       'en' => 'Cancelled'],
                'no_show'   => ['es' => 'No se presentó',  'en' => 'No show'],
              ];
              echo $statusLabels[$appointment->status][$locale] ?? $appointment->status;
            @endphp
          </p>
        </div>

        <!-- Detalles de la cita -->
        <div class="appt-card">
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
                <span class="badge status-{{ $appointment->status }}">
                  {{ substr($appointment->start_time, 0, 5) }} – {{ substr($appointment->end_time, 0, 5) }}
                </span>
              </p>
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
      </div>

      <!-- Footer -->
      <div class="footer">
        <p>{{ $locale === 'en' ? 'This is an automated message, please do not reply.' : 'Este es un mensaje automático, por favor no respondas.' }}</p>
        <p style="margin-top: 8px;">© {{ date('Y') }} {{ $locale === 'en' ? 'Appointment System' : 'Sistema de Citas' }}.</p>
      </div>

    </div>
  </div>
</body>
</html>