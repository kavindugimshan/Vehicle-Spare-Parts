@echo off
setlocal enabledelayedexpansion

rem scripts/setup_db.bat - one-command database creation and seeding
rem for WAMP on Windows. Run from the project root:
rem     scripts\setup_db.bat
rem
rem Uses WAMP's bundled mysql client and default root account with no
rem password. Edit MYSQL_BIN below if your WAMP version's MySQL folder
rem name is different.

set MYSQL_BIN="C:\wamp64\bin\mysql\mysql8.0.31\bin\mysql.exe"
set DB_NAME=vspms_db
set DB_USER=root
set DB_PASS=

if not exist %MYSQL_BIN% (
    echo Could not find mysql.exe at %MYSQL_BIN%
    echo Edit scripts\setup_db.bat and point MYSQL_BIN at your WAMP MySQL bin folder.
    exit /b 1
)

echo Importing schema.sql...
%MYSQL_BIN% -u%DB_USER% %DB_PASS% < database\schema.sql
if errorlevel 1 goto :error

for %%f in (database\seed_*.sql) do (
    echo Importing %%f...
    %MYSQL_BIN% -u%DB_USER% %DB_PASS% %DB_NAME% < "%%f"
    if errorlevel 1 goto :error
)

echo Done. Database "%DB_NAME%" is ready.
exit /b 0

:error
echo Setup failed - see the error above.
exit /b 1
