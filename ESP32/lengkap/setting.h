#ifndef SETTING_H
#define SETTING_H

const char settingsPage[] PROGMEM = R"rawliteral(
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan WiFi & Server</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; background: #f4f4f4; }
        .container { max-width: 400px; margin: auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1); }
        input, button { width: 100%; padding: 10px; margin: 5px 0; border: 1px solid #ccc; border-radius: 5px; }
        button { background: #28a745; color: white; cursor: pointer; }
        button:hover { background: #218838; }
    </style>
</head>
<body>
    <div class="container">
        <h2>🔧 Pengaturan WiFi & Server</h2>
        <form action="/save" method="POST">
            <label>SSID:</label>
            <input type="text" name="ssid" value="%SSID%">
            
            <label>Password:</label>
            <input type="password" name="password" value="%PASSWORD%">
            
            <label>Server URL:</label>
            <input type="text" name="serverUrl" value="%SERVER%">
            
            <label>API Key:</label>
            <input type="text" name="apiKey" value="%APIKEY%">
            
            <button type="submit">Simpan</button>
        </form>
    </div>
</body>
</html>
)rawliteral";

#endif
