@echo off
REM Merge 10 text files with separators (silent mode)
REM Delete output file if exists
if exist UI_Files.txt del UI_Files.txt

setlocal enabledelayedexpansion

REM File definitions - customize these if needed
set file1=ai-assistant-test\services\diet\template-parts\form.php
set file2=ai-assistant-test\assets\js\services\diet\form-events.js
set file3=ai-assistant-test\assets\js\services\diet\form-inputs.js
set file4=ai-assistant-test\assets\js\services\diet\form-steps.js
set file5=ai-assistant-test\assets\js\services\diet\form-validation.js
set file6=ai-assistant-test\assets\js\services\diet\script.js
set file7=ai-assistant-test\assets\js\services\diet\diet.js
set file8=ai-assistant-test\assets\css\services\diet.css
set file9=ai-assistant-test\functions.php
set file10=ai-assistant-test\assets\js\auto-fill.js

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
                echo. >> UI_Files.txt
                echo ------------------------------------------------------------------- >> UI_Files.txt
                echo. >> UI_Files.txt
            )
            type !file%%i! >> UI_Files.txt
            set first_file=0
        ) else (
            echo Warning: !file%%i! not found
        )
    )
)

echo Merge completed. Output saved to UI_Files.txt
pause