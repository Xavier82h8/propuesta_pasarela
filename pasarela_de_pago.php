<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Pago Mejorado</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #4285f4;
            --primary-green: #34a853;
            --dark-text: #202124;
            --light-gray: #e0e0e0;
            --error-red: #ea4335;
            --warning-yellow: #fbbc05;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
            position: relative;
            overflow-x: hidden;
        }
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                radial-gradient(circle at 20% 30%, rgba(102, 126, 234, 0.4) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(118, 75, 162, 0.4) 0%, transparent 50%);
            animation: bgPulse 15s ease-in-out infinite;
            pointer-events: none;
            z-index: 0;
        }
        @keyframes bgPulse {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 0.8; }
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 420px;
            gap: 32px;
            position: relative;
            z-index: 1;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(40px) saturate(180%);
            border-radius: 28px;
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow:
                0 20px 60px rgba(0, 0, 0, 0.15),
                0 8px 16px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 1);
            animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .glass-card:hover {
            transform: translateY(-4px);
            box-shadow:
                0 24px 70px rgba(0, 0, 0, 0.2),
                0 12px 24px rgba(0, 0, 0, 0.15),
                inset 0 1px 0 rgba(255, 255, 255, 1);
        }
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(60px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .left-section {
            padding: 56px;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 48px;
        }
        .logo-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-green) 100%);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 30px;
            font-weight: bold;
            box-shadow:
                0 12px 24px rgba(66, 133, 244, 0.3),
                0 6px 12px rgba(52, 168, 83, 0.2);
            animation: logoFloat 3s ease-in-out infinite;
        }
        @keyframes logoFloat {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
        }
        .logo-text {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-green) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.5px;
        }
        .main-title {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 16px;
            color: var(--dark-text);
            letter-spacing: -1px;
        }
        .subtitle {
            font-size: 17px;
            color: #5f6368;
            margin-bottom: 48px;
            line-height: 1.6;
        }
        .section {
            background: white;
            border-radius: 24px;
            padding: 40px;
            margin-bottom: 28px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        .section:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }
        .section-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 32px;
        }
        .form-row {
            display: flex;
            gap: 24px;
            margin-bottom: 24px;
        }
        .input-group {
            flex: 1;
            position: relative;
        }
        .input-label {
            font-size: 14px;
            font-weight: 600;
            color: #5f6368;
            margin-bottom: 10px;
            display: block;
        }
        .input-field {
            width: 100%;
            height: 56px;
            border: 2px solid #dadce0;
            border-radius: 14px;
            padding: 0 48px 0 18px;
            font-size: 16px;
            color: var(--dark-text);
            transition: all 0.3s ease;
            background: white;
            font-weight: 500;
        }
        .input-field:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 4px rgba(66, 133, 244, 0.15);
        }
        .input-field.completed {
            border-color: var(--primary-green);
            background: linear-gradient(135deg, #f8fbf8 0%, #f0f8f2 100%);
        }
        .input-field.invalid {
            border-color: var(--error-red);
            background: #fef7f6;
            animation: shake 0.4s cubic-bezier(.36, .07, .19, .97);
        }
        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-3px, 0, 0); }
            40%, 60% { transform: translate3d(3px, 0, 0); }
        }
        .input-icon {
            position: absolute;
            right: 18px;
            top: 48px;
            color: var(--primary-green);
            opacity: 0;
            transform: scale(0.5) rotate(-180deg);
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            font-size: 18px;
        }
        .input-field.completed + .input-icon {
            opacity: 1;
            transform: scale(1) rotate(0deg);
        }
        .custom-select {
            position: relative;
        }
        .select-trigger {
            width: 100%;
            height: 56px;
            border: 2px solid #dadce0;
            border-radius: 14px;
            padding: 0 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            background: white;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        .select-trigger:hover {
            border-color: var(--primary-blue);
            box-shadow: 0 4px 12px rgba(66, 133, 244, 0.1);
        }
        .select-trigger.active {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 4px rgba(66, 133, 244, 0.15);
        }
        .select-trigger.completed {
            border-color: var(--primary-green);
            background: linear-gradient(135deg, #f8fbf8 0%, #f0f8f2 100%);
        }
        .selected-option {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
            color: #5f6368;
        }
        .select-trigger.completed .selected-option {
            color: var(--dark-text);
            font-weight: 600;
        }
        .select-options {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            width: 100%;
            background: white;
            border-radius: 14px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid var(--primary-blue);
        }
        .select-options.active {
            max-height: 320px;
            opacity: 1;
            padding: 8px;
            overflow-y: auto;
        }
        .select-option {
            padding: 14px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            border-radius: 10px;
            font-weight: 500;
        }
        .select-option:hover {
            background: linear-gradient(135deg, rgba(66, 133, 244, 0.1) 0%, rgba(52, 168, 83, 0.1) 100%);
            transform: translateX(4px);
        }
        .phone-input-wrapper {
            display: flex;
            gap: 12px;
        }
        .phone-code {
            width: 100px;
            height: 56px;
            border: 2px solid #dadce0;
            border-radius: 14px;
            padding: 0 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            font-weight: 600;
            color: var(--dark-text);
        }
        .phone-input {
            flex: 1;
        }
        .phone-format-hint {
            font-size: 12px;
            color: #5f6368;
            margin-top: 6px;
            font-style: italic;
        }

        /* CORRECCIÓN DEL FLIP DE LA TARJETA */
        .card-container {
            margin-bottom: 40px;
            perspective: 1500px;
            /* Altura fija para evitar cambios */
            height: 290px;
        }
        .card-flip-container {
            position: relative;
            width: 100%;
            max-width: 460px;
            height: 100%; /* Usa el 100% del contenedor padre */
            margin: 0 auto;
            transition: transform 0.7s cubic-bezier(0.4, 0, 0.2, 1);
            transform-style: preserve-3d;
            /* CRÍTICO: Eliminar cualquier transform que no sea rotateY */
        }
        .card-flip-container.flipped {
            transform: rotateY(180deg);
        }
        .card-face {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            border-radius: 24px;
            padding: 32px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            /* Asegurar que no haya movimiento vertical */
            top: 0;
            left: 0;
        }
        .card-front {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
        }
        .card-front::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at center, rgba(255, 255, 255, 0.15) 0%, transparent 50%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .card-front:hover::before {
            opacity: 1;
            animation: cardShine 3s ease-in-out infinite;
        }
        @keyframes cardShine {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }
        .card-front.visa {
            background: linear-gradient(135deg, #1A1F71 0%, #0D47A1 100%);
            animation: cardEntry 0.6s ease-out;
            box-shadow: 0 20px 60px rgba(26, 31, 113, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }
        .card-front.mastercard {
            background: linear-gradient(135deg, #EB001B 0%, #F79E1B 50%, #FF5F00 100%);
            animation: cardEntry 0.6s ease-out;
            box-shadow: 0 20px 60px rgba(235, 0, 27, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.15);
        }
        .card-front.nu {
            background: linear-gradient(135deg, #820AD1 0%, #5E008F 50%, #450066 100%);
            position: relative;
            animation: cardEntry 0.6s ease-out;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 20px 60px rgba(130, 10, 209, 0.6), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }
        @keyframes cardEntry {
            0% {
                filter: brightness(1);
            }
            50% {
                filter: brightness(1.2);
            }
            100% {
                filter: brightness(1);
            }
        }
        .card-front.card-invalid {
            animation: cardShakeError 0.5s cubic-bezier(.36, .07, .19, .97);
        }
        @keyframes cardShakeError {
            10%, 90% { transform: translate3d(-3px, 0, 0); }
            20%, 80% { transform: translate3d(5px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-7px, 0, 0); }
            40%, 60% { transform: translate3d(7px, 0, 0); }
        }
        .card-back {
            background: linear-gradient(135deg, #434343 0%, #000000 100%);
            transform: rotateY(180deg);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
        }
        .card-back.visa {
            background: linear-gradient(135deg, #1A1F71 0%, #0D47A1 100%);
            box-shadow: 0 20px 60px rgba(26, 31, 113, 0.5);
        }
        .card-back.mastercard {
            background: linear-gradient(135deg, #EB001B 0%, #F79E1B 50%, #FF5F00 100%);
            box-shadow: 0 20px 60px rgba(235, 0, 27, 0.5);
        }
        .card-back.nu {
            background: linear-gradient(135deg, #5E008F 0%, #3C005B 50%, #2A0044 100%);
            box-shadow: 0 20px 60px rgba(94, 0, 143, 0.5);
        }
        .card-visual {
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            z-index: 1;
        }
        .card-logo-container {
            display: flex;
            justify-content: flex-end;
            font-size: 36px;
            color: white;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
        }
        .card-chip {
            width: 54px;
            height: 44px;
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 50%, #B8860B 100%);
            border-radius: 10px;
            position: relative;
            box-shadow:
                inset 0 3px 6px rgba(255, 255, 255, 0.4),
                inset 0 -3px 6px rgba(0, 0, 0, 0.4),
                0 3px 10px rgba(0, 0, 0, 0.3);
        }
        .card-chip::before {
            content: '';
            position: absolute;
            top: 8px;
            left: 8px;
            right: 8px;
            bottom: 8px;
            background:
                repeating-linear-gradient(90deg, rgba(0, 0, 0, 0.15) 0px, rgba(0, 0, 0, 0.15) 1px, transparent 1px, transparent 4px),
                repeating-linear-gradient(0deg, rgba(0, 0, 0, 0.15) 0px, rgba(0, 0, 0, 0.15) 1px, transparent 1px, transparent 4px);
            border-radius: 6px;
        }
        .card-number-display {
            font-size: 26px;
            letter-spacing: 4px;
            font-weight: 600;
            color: white;
            margin: 24px 0;
            text-shadow: 0 3px 6px rgba(0, 0, 0, 0.4);
            font-family: 'Courier New', monospace;
        }
        .card-details-display {
            display: flex;
            justify-content: space-between;
        }
        .card-detail-label {
            font-size: 10px;
            text-transform: uppercase;
            opacity: 0.8;
            color: white;
            letter-spacing: 1px;
            font-weight: 600;
        }
        .card-detail-value {
            font-size: 17px;
            font-weight: 700;
            color: white;
            margin-top: 6px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            text-transform: uppercase;
        }
        .card-magnetic-strip {
            width: 100%;
            height: 56px;
            background: linear-gradient(180deg, #000 0%, #1a1a1a 50%, #000 100%);
            margin: 24px 0;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.5);
        }
        .card-cvc-display {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 14px;
            background: white;
            padding: 12px 24px;
            border-radius: 10px;
            margin-top: 36px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        .card-cvc-label {
            font-size: 13px;
            color: #5f6368;
            font-weight: 600;
        }
        .card-cvc-value {
            font-size: 20px;
            font-weight: 700;
            color: var(--dark-text);
            font-family: 'Courier New', monospace;
        }
        .card-input-grid {
            display: grid;
            gap: 24px;
        }
        .card-input-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        .card-brand-icon {
            position: absolute;
            right: 18px;
            top: 48px;
            font-size: 32px;
            opacity: 0.3;
            transition: all 0.3s ease;
        }
        .card-brand-icon.active {
            opacity: 1;
            animation: iconPop 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }
        @keyframes iconPop {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        .right-section {
            position: sticky;
            top: 40px;
            height: fit-content;
        }
        .summary-card {
            padding: 40px;
        }
        .summary-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 28px;
        }
        .plan-badge {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-green) 100%);
            border-radius: 20px;
            padding: 28px;
            color: white;
            margin-bottom: 28px;
            box-shadow: 0 12px 32px rgba(66, 133, 244, 0.3);
        }
        .plan-name {
            font-size: 16px;
            margin-bottom: 12px;
            opacity: 0.95;
            font-weight: 600;
        }
        .plan-price {
            font-size: 42px;
            font-weight: 800;
            margin-bottom: 6px;
        }
        .plan-period {
            font-size: 15px;
            opacity: 0.85;
            font-weight: 500;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 18px;
            font-size: 15px;
            color: #5f6368;
            font-weight: 500;
        }
        .summary-item.total {
            font-size: 22px;
            font-weight: 700;
            color: var(--dark-text);
            padding-top: 20px;
            border-top: 2px solid rgba(0, 0, 0, 0.1);
            margin-top: 12px;
        }
        .pay-button {
            width: 100%;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-green) 100%);
            border: none;
            border-radius: 14px;
            color: white;
            font-size: 17px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            transition: all 0.3s ease;
            box-shadow: 0 12px 24px rgba(66, 133, 244, 0.3);
        }
        .pay-button:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 16px 32px rgba(66, 133, 244, 0.4);
        }
        @media (max-width: 1024px) {
            .container {
                grid-template-columns: 1fr;
            }
            .right-section {
                position: static;
            }
        }
        @media (max-width: 768px) {
            body {
                padding: 20px 16px;
            }
            .left-section, .summary-card {
                padding: 32px 24px;
            }
            .form-row, .card-input-row {
                flex-direction: column;
                grid-template-columns: 1fr;
            }
            .main-title {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="glass-card left-section">
            <div class="logo">
                <div class="logo-icon">💳</div>
                <div class="logo-text">PayFlow</div>
            </div>

            <h1 class="main-title">Información de Pago</h1>
            <p class="subtitle">Complete sus datos de forma segura para procesar el pago</p>

            <div class="section">
                <h2 class="section-title">Información Personal</h2>
                <div class="form-row">
                    <div class="input-group">
                        <label class="input-label">Nombre Completo</label>
                        <input type="text" class="input-field" id="fullName" placeholder="Juan Pérez">
                        <i class="fas fa-check input-icon"></i>
                    </div>
                    <div class="input-group">
                        <label class="input-label">Correo Electrónico</label>
                        <input type="email" class="input-field" id="email" placeholder="ejemplo@correo.com">
                        <i class="fas fa-check input-icon"></i>
                    </div>
                </div>
                <div class="form-row">
                    <div class="input-group">
                        <label class="input-label">País</label>
                        <div class="custom-select">
                            <div class="select-trigger" id="countryTrigger">
                                <span class="selected-option">
                                    <span>Seleccione su país</span>
                                </span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="select-options" id="countryOptions">
                                <div class="select-option" data-country="MX" data-code="+52" data-format="(###) ###-####">
                                    <span>🇲🇽</span>
                                    <span>México (+52)</span>
                                </div>
                                <div class="select-option" data-country="US" data-code="+1" data-format="(###) ###-####">
                                    <span>🇺🇸</span>
                                    <span>Estados Unidos (+1)</span>
                                </div>
                                <div class="select-option" data-country="ES" data-code="+34" data-format="### ### ###">
                                    <span>🇪🇸</span>
                                    <span>España (+34)</span>
                                </div>
                                <div class="select-option" data-country="AR" data-code="+54" data-format="(###) ####-####">
                                    <span>🇦🇷</span>
                                    <span>Argentina (+54)</span>
                                </div>
                                <div class="select-option" data-country="CO" data-code="+57" data-format="(###) ###-####">
                                    <span>🇨🇴</span>
                                    <span>Colombia (+57)</span>
                                </div>
                                <div class="select-option" data-country="CL" data-code="+56" data-format="# #### ####">
                                    <span>🇨🇱</span>
                                    <span>Chile (+56)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="input-group">
                        <label class="input-label">Teléfono</label>
                        <div class="phone-input-wrapper">
                            <div class="phone-code" id="phoneCode">+52</div>
                            <div class="phone-input">
                                <input type="tel" class="input-field" id="phone" placeholder="Número de teléfono">
                                <i class="fas fa-check input-icon"></i>
                            </div>
                        </div>
                        <div class="phone-format-hint" id="phoneFormatHint">Formato: (###) ###-####</div>
                    </div>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">Información de la Tarjeta</h2>

                <div class="card-container">
                    <div class="card-flip-container" id="cardFlip">
                        <div class="card-face card-front" id="cardFront">
                            <div class="card-visual">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <div class="card-chip"></div>
                                    <div class="card-logo-container" id="cardBrandLogo">
                                        <i class="fas fa-credit-card"></i>
                                    </div>
                                </div>
                                <div class="card-number-display" id="cardNumberDisplay">#### #### #### ####</div>
                                <div class="card-details-display">
                                    <div>
                                        <div class="card-detail-label">Titular</div>
                                        <div class="card-detail-value" id="cardNameDisplay">NOMBRE APELLIDO</div>
                                    </div>
                                    <div>
                                        <div class="card-detail-label">Vence</div>
                                        <div class="card-detail-value" id="cardExpiryDisplay">MM/AA</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-face card-back" id="cardBack">
                            <div class="card-visual">
                                <div class="card-magnetic-strip"></div>
                                <div class="card-cvc-display">
                                    <span class="card-cvc-label">CVV</span>
                                    <span class="card-cvc-value" id="cardCvcDisplay">•••</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-input-grid">
                    <div class="input-group">
                        <label class="input-label">Número de Tarjeta</label>
                        <input type="text" class="input-field" id="cardNumber" placeholder="1234 5678 9012 3456" maxlength="19">
                        <div class="card-brand-icon" id="cardBrandIcon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                    </div>
                    <div class="input-group">
                        <label class="input-label">Nombre en la Tarjeta</label>
                        <input type="text" class="input-field" id="cardName" placeholder="Como aparece en la tarjeta">
                        <i class="fas fa-check input-icon"></i>
                    </div>
                    <div class="card-input-row">
                        <div class="input-group">
                            <label class="input-label">Fecha de Vencimiento</label>
                            <input type="text" class="input-field" id="cardExpiry" placeholder="MM/AA" maxlength="5">
                            <i class="fas fa-check input-icon"></i>
                        </div>
                        <div class="input-group">
                            <label class="input-label">CVV</label>
                            <input type="text" class="input-field" id="cardCvc" placeholder="123" maxlength="4">
                            <i class="fas fa-check input-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="glass-card right-section">
            <div class="summary-card">
                <h2 class="summary-title">Resumen del Pedido</h2>
                <div class="plan-badge">
                    <div class="plan-name">Plan Premium</div>
                    <div class="plan-price">$49.99</div>
                    <div class="plan-period">por mes</div>
                </div>
                <div class="summary-item">
                    <span>Subtotal</span>
                    <span>$49.99</span>
                </div>
                <div class="summary-item">
                    <span>IVA (16%)</span>
                    <span>$8.00</span>
                </div>
                <div class="summary-item total">
                    <span>Total</span>
                    <span>$57.99</span>
                </div>
                <button class="pay-button" id="payButton">
                    <i class="fas fa-lock"></i>
                    <span>Pagar Ahora</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        // Datos de países con ladas y formatos
        const countryData = {
            'MX': { code: '+52', format: '(###) ###-####', placeholder: '(555) 123-4567' },
            'US': { code: '+1', format: '(###) ###-####', placeholder: '(555) 123-4567' },
            'ES': { code: '+34', format: '### ### ###', placeholder: '612 345 678' },
            'AR': { code: '+54', format: '(###) ####-####', placeholder: '(11) 1234-5678' },
            'CO': { code: '+57', format: '(###) ###-####', placeholder: '(300) 123-4567' },
            'CL': { code: '+56', format: '# #### ####', placeholder: '9 1234 5678' }
        };

        let selectedCountry = null;

        // Selector de país
        const countryTrigger = document.getElementById('countryTrigger');
        const countryOptions = document.getElementById('countryOptions');
        const phoneCode = document.getElementById('phoneCode');
        const phoneInput = document.getElementById('phone');
        const phoneFormatHint = document.getElementById('phoneFormatHint');

        countryTrigger.addEventListener('click', () => {
            countryOptions.classList.toggle('active');
            countryTrigger.classList.toggle('active');
        });

        document.querySelectorAll('.select-option').forEach(option => {
            option.addEventListener('click', function() {
                const country = this.dataset.country;
                const code = this.dataset.code;
                const flag = this.querySelector('span').textContent;
                const name = this.textContent.trim();

                selectedCountry = country;
                const data = countryData[country];

                // Actualizar selector
                countryTrigger.querySelector('.selected-option').innerHTML = `
                    <span>${flag}</span>
                    <span>${name}</span>
                `;
                countryTrigger.classList.add('completed');

                // Actualizar código de teléfono
                phoneCode.textContent = data.code;

                // Actualizar placeholder y formato
                phoneInput.placeholder = data.placeholder;
                phoneFormatHint.textContent = `Formato: ${data.format}`;

                // Limpiar campo de teléfono
                phoneInput.value = '';
                phoneInput.classList.remove('completed', 'invalid');

                countryOptions.classList.remove('active');
                countryTrigger.classList.remove('active');
            });
        });

        // Cerrar selector al hacer clic fuera
        document.addEventListener('click', (e) => {
            if (!countryTrigger.contains(e.target) && !countryOptions.contains(e.target)) {
                countryOptions.classList.remove('active');
                countryTrigger.classList.remove('active');
            }
        });

        // Formatear teléfono según el país
        phoneInput.addEventListener('input', function(e) {
            if (!selectedCountry) return;

            let value = e.target.value.replace(/\D/g, '');
            const format = countryData[selectedCountry].format;
            let formatted = '';
            let valueIndex = 0;

            for (let i = 0; i < format.length && valueIndex < value.length; i++) {
                if (format[i] === '#') {
                    formatted += value[valueIndex];
                    valueIndex++;
                } else {
                    formatted += format[i];
                }
            }

            e.target.value = formatted;

            // Validar longitud
            const expectedLength = format.replace(/[^#]/g, '').length;
            if (value.length === expectedLength) {
                e.target.classList.add('completed');
                e.target.classList.remove('invalid');
            } else {
                e.target.classList.remove('completed');
            }
        });

        // Validación de campos básicos
        const fullName = document.getElementById('fullName');
        const email = document.getElementById('email');

        fullName.addEventListener('input', function() {
            if (this.value.trim().length >= 3) {
                this.classList.add('completed');
                this.classList.remove('invalid');
            } else {
                this.classList.remove('completed');
            }
        });

        email.addEventListener('input', function() {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (emailRegex.test(this.value)) {
                this.classList.add('completed');
                this.classList.remove('invalid');
            } else {
                this.classList.remove('completed');
            }
        });

        // Tarjeta de crédito
        const cardFlip = document.getElementById('cardFlip');
        const cardFront = document.getElementById('cardFront');
        const cardBack = document.getElementById('cardBack');
        const cardNumber = document.getElementById('cardNumber');
        const cardName = document.getElementById('cardName');
        const cardExpiry = document.getElementById('cardExpiry');
        const cardCvc = document.getElementById('cardCvc');
        const cardNumberDisplay = document.getElementById('cardNumberDisplay');
        const cardNameDisplay = document.getElementById('cardNameDisplay');
        const cardExpiryDisplay = document.getElementById('cardExpiryDisplay');
        const cardCvcDisplay = document.getElementById('cardCvcDisplay');
        const cardBrandIcon = document.getElementById('cardBrandIcon');
        const cardBrandLogo = document.getElementById('cardBrandLogo');

        // Detectar tipo de tarjeta
        function detectCardBrand(number) {
            const patterns = {
                visa: /^4/,
                mastercard: /^5[1-5]/,
                amex: /^3[47]/,
                discover: /^6(?:011|5)/
            };

            for (let brand in patterns) {
                if (patterns[brand].test(number)) {
                    return brand;
                }
            }
            return null;
        }

        // Algoritmo de Luhn
        function luhnCheck(cardNumber) {
            let sum = 0;
            let isEven = false;

            for (let i = cardNumber.length - 1; i >= 0; i--) {
                let digit = parseInt(cardNumber[i]);

                if (isEven) {
                    digit *= 2;
                    if (digit > 9) {
                        digit -= 9;
                    }
                }

                sum += digit;
                isEven = !isEven;
            }

            return sum % 10 === 0;
        }

        // Formatear número de tarjeta
        cardNumber.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '');
            let formatted = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = formatted;

            cardNumberDisplay.textContent = formatted.padEnd(19, '#').replace(/\s/g, ' ');

            const brand = detectCardBrand(value);
            if (brand) {
                updateCardBrand(brand);
            }

            if (value.length >= 13) {
                if (luhnCheck(value)) {
                    e.target.classList.add('completed');
                    e.target.classList.remove('invalid');
                    cardFront.classList.remove('card-invalid');
                } else {
                    e.target.classList.add('invalid');
                    e.target.classList.remove('completed');
                    cardFront.classList.add('card-invalid');
                }
            }
        });

        function updateCardBrand(brand) {
            const icons = {
                visa: '<i class="fab fa-cc-visa"></i>',
                mastercard: '<i class="fab fa-cc-mastercard"></i>',
                amex: '<i class="fab fa-cc-amex"></i>',
                discover: '<i class="fab fa-cc-discover"></i>'
            };

            cardBrandIcon.innerHTML = icons[brand] || '<i class="fas fa-credit-card"></i>';
            cardBrandLogo.innerHTML = icons[brand] || '<i class="fas fa-credit-card"></i>';
            cardBrandIcon.classList.add('active');

            cardFront.className = 'card-face card-front ' + brand;
            cardBack.className = 'card-face card-back ' + brand;
        }

        cardName.addEventListener('input', function() {
            cardNameDisplay.textContent = this.value.toUpperCase() || 'NOMBRE APELLIDO';
            if (this.value.trim().length >= 3) {
                this.classList.add('completed');
            } else {
                this.classList.remove('completed');
            }
        });

        cardExpiry.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.slice(0, 2) + '/' + value.slice(2, 4);
            }
            e.target.value = value;
            cardExpiryDisplay.textContent = value || 'MM/AA';

            if (value.length === 5) {
                const [month, year] = value.split('/');
                if (parseInt(month) >= 1 && parseInt(month) <= 12) {
                    this.classList.add('completed');
                    this.classList.remove('invalid');
                } else {
                    this.classList.add('invalid');
                    this.classList.remove('completed');
                }
            }
        });

        cardCvc.addEventListener('focus', function() {
            cardFlip.classList.add('flipped');
        });

        cardCvc.addEventListener('blur', function() {
            cardFlip.classList.remove('flipped');
        });

        cardCvc.addEventListener('input', function() {
            cardCvcDisplay.textContent = this.value || '•••';
            if (this.value.length >= 3) {
                this.classList.add('completed');
            } else {
                this.classList.remove('completed');
            }
        });

        // Botón de pago
        const payButton = document.getElementById('payButton');
        payButton.addEventListener('click', function() {
            alert('¡Formulario de demostración! Todas las validaciones funcionan correctamente.');
        });
    </script>
</body>
</html>