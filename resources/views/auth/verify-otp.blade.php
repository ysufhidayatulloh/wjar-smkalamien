<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <title>Verifikasi OTP</title>
    <link rel="icon" type="image/x-icon" href="{{ url('/assets/img') }}/cbt-malela.png" />
    <!-- BEGIN GLOBAL MANDATORY STYLES -->
    <link href="https://fonts.googleapis.com/css?family=Quicksand:400,500,600,700&display=swap" rel="stylesheet">
    <link href="{{ url('/assets/cbt-malela') }}/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="{{ url('/assets/cbt-malela') }}/assets/css/plugins.css" rel="stylesheet" type="text/css" />
    <link href="{{ url('/assets/cbt-malela') }}/assets/css/authentication/form-2.css" rel="stylesheet" type="text/css" />
    <!-- END GLOBAL MANDATORY STYLES -->
    <link rel="stylesheet" type="text/css" href="{{ url('/assets/cbt-malela') }}/assets/css/forms/theme-checkbox-radio.css">
    <link rel="stylesheet" type="text/css" href="{{ url('/assets/cbt-malela') }}/assets/css/forms/switches.css">
    <style>
        #otp-field {
            display: flex;
            flex-direction: column;
            gap: 10px;
            position: relative;
            margin: 0;
        }

        #otp-field .input-wrapper {
            display: flex;
            align-items: center;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 8px;
            background-color: #fff;
            transition: border-color 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
        }

        #otp-field .input-wrapper:focus-within {
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
        }

        #otp-field input {
            flex-grow: 1;
            border: none;
            outline: none;
            font-size: 16px;
            color: #333;
            background-color: transparent;
            padding-left: 0;
        }

        #otp-field input::placeholder {
            color: #aaa;
        }

        #resend-button:disabled {
            cursor: not-allowed;
            color: gray;
        }

        .form-content {
            padding-left: 0;
        }
    </style>
    <script src="{{ url('/assets/cbt-malela') }}/assets/js/libs/jquery-3.1.1.min.js"></script>
    <script src="{{ url('/assets/cbt-malela') }}/plugins/sweetalerts/sweetalert2.min.js"></script>
    <script src="{{ url('/assets/cbt-malela') }}/plugins/sweetalerts/custom-sweetalert.js"></script>
</head>

<body class="form no-image-content">
    <div class="form-container outer">
        <div class="form-form">
            <div class="form-form-wrap">
                <div class="form-container">
                    <div class="form-content">

                        <h1 class="">Verifikasi OTP</h1>
                        <p class="signup-link recovery">Masukkan kode OTP yang telah dikirim ke email Anda.</p>
                        <form action="{{ url('/verify_otp') }}" method="post" class="text-left">
                            @csrf
                            <div class="form">
                                <div id="otp-field" class="field-wrapper input mb-2">
                                    <div class="d-flex justify-content-between">
                                        <label for="otp">Kode OTP</label>
                                        <span id="char-counter">0/6</span>
                                    </div>
                                    <div class="input-wrapper">
                                        <input id="otp" name="otp" type="text" class="form-control" placeholder="Masukkan OTP" maxlength="6" required oninput="validateOtpInput(this)">
                                    </div>
                                </div>

                                <div class="d-sm-flex justify-content-between">
                                    <div class="field-wrapper">
                                        <button type="submit" class="btn btn-primary">Verifikasi OTP</button>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <p class="signup-link recovery mt-3">
                            Belum menerima kode? 
                            <form action="{{ url('/resend_otp') }}" method="POST" style="display: inline;">
                                @csrf
                                <input type="hidden" name="email" value="{{ session('otp_email') }}">
                                <button type="submit" id="resend-button" class="btn btn-link p-0 m-0 align-baseline" style="color: #1C3FAA;" disabled>
                                    Kirim Ulang (<span id="countdown">30</span>s)
                                </button>
                            </form>
                        </p>

                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(session('pesan'))
    <script>
        const pesan = "{{ session('pesan') }}";
        const isError = pesan.includes("tidak valid") || pesan.includes("kadaluarsa");

        swal({
            title: isError ? 'Gagal!' : 'Berhasil!',
            text: pesan,
            type: isError ? 'error' : 'success',
            padding: '2em'
        });
    </script>
    @endif

    <script>
        function validateOtpInput(input) {
            const maxLength = 6;
            input.value = input.value.replace(/[^0-9]/g, '').slice(0, maxLength);
            document.getElementById('char-counter').innerText = `${input.value.length}/${maxLength}`;
        }

        document.addEventListener("DOMContentLoaded", function () {
            const resendButton = document.getElementById('resend-button');
            const countdownSpan = document.getElementById('countdown');
            let timer;

            function startCountdown() {
                let countdown = 30;
                resendButton.setAttribute("disabled", true);
                resendButton.style.cursor = "not-allowed";
                countdownSpan.textContent = countdown;

                if (timer) clearInterval(timer);

                timer = setInterval(() => {
                    countdown--;
                    countdownSpan.textContent = countdown;

                    if (countdown <= 0) {
                        clearInterval(timer);
                        timer = null;
                        resendButton.removeAttribute("disabled");
                        resendButton.style.cursor = "pointer";
                        countdownSpan.textContent = "30";
                        resendButton.textContent = "Kirim Ulang";
                    }
                }, 1000);
            }

            resendButton.addEventListener("click", function (e) {
                e.preventDefault();
                const form = resendButton.closest("form");
                startCountdown();
                form.submit();
            });

            startCountdown(); // Initialize countdown on page load
        });
    </script>
</body>

</html>
