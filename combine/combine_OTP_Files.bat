@echo off
REM Merge 10 text files with separators (silent mode)
REM Delete output file if exists
if exist OTP_Files.txt del OTP_Files.txt

setlocal enabledelayedexpansion

REM File definitions - customize these if needed
set file1=ai-assistant-test\modules\otp\otp-assets\otp.css
set file2=ai-assistant-test\modules\otp\otp-assets\otp.js
set file3=ai-assistant-test\modules\otp\class-otp-handler.php
set file4=ai-assistant-test\modules\otp\otp-ajax.php
set file5=ai-assistant-test\modules\otp\otp-login-template.php
set file6=ai-assistant-test\functions.php
set file7=
set file8=
set file9=
set file10=

REM Default merge settings (1=merge, 0=skip)
set merge1=1
set merge2=1
set merge3=1
set merge4=1
set merge5=1
set merge6=1
set merge7=1
set merge8=1
set merge9=1
set merge10=1

echo Starting file merge operation...

set first_file=1

REM Process each file
for /l %%i in (1,1,10) do (
    if !merge%%i! equ 1 (
        if exist !file%%i! (
            if !first_file! equ 0 (
                echo. >> OTP_Files.txt
                echo ------------------------------------------------------------------- >> OTP_Files.txt
                echo. >> OTP_Files.txt
            )
            type !file%%i! >> OTP_Files.txt
            set first_file=0
        ) else (
            echo Warning: !file%%i! not found
        )
    )
)

echo Merge completed. Output saved to OTP_Files.txt
pause