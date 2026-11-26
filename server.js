// ==========================================================================
// VAST STUDIO — PHP Development Server Runner
// Automatically uses Laragon, XAMPP, or System PHP
// Run: npm run dev or node server.js
// ==========================================================================

const { spawn } = require('child_process');
const fs = require('fs');
const path = require('path');

const PORT = process.env.PORT || 3000;
const HOST = '127.0.0.1';

// Search for Laragon PHP or global php
const possiblePhpPaths = [
    'C:\\xampp\\php\\php.exe',
    'C:\\laragon\\bin\\php\\php-8.1.10-Win32-vs16-x64\\php.exe',
    'C:\\laragon\\bin\\php\\php-8.3.30-Win32-vs16-x64\\php.exe',
    'C:\\laragon\\bin\\php\\php-8.2\\php.exe',
    'C:\\laragon\\bin\\php\\php-8.0\\php.exe',
    'php'
];

let phpExe = 'php';
for (const p of possiblePhpPaths) {
    if (p !== 'php' && fs.existsSync(p)) {
        phpExe = p;
        break;
    }
}

console.log('\n');
console.log('╔═══════════════════════════════════════════════════════════════════╗');
console.log('║                                                                   ║');
console.log('║         ██╗   ██╗ █████╗ ███████╗████████╗  ███████╗████████╗     ║');
console.log('║         ██║   ██║██╔══██╗██╔════╝╚══██╔══╝  ██╔════╝╚══██╔══╝     ║');
console.log('║         ██║   ██║███████║███████╗   ██║     ███████╗   ██║        ║');
console.log('║         ╚██╗ ██╔╝██╔══██║╚════██║   ██║     ╚════██║   ██║        ║');
console.log('║          ╚████╔╝ ██║  ██║███████║   ██║     ███████║   ██║        ║');
console.log('║           ╚═══╝  ╚═╝  ╚═╝╚══════╝   ╚═╝     ╚══════╝   ╚═╝        ║');
console.log('║                                                                   ║');
console.log('║                 VAST STUDIO — STREETWEAR CORE                     ║');
console.log('║                                                                   ║');
console.log(`║  ➜ Local:        http://localhost:${PORT}                            ║`);
console.log('║                                                                   ║');
console.log('║  Nhấn Ctrl + C để dừng máy chủ.                                   ║');
console.log('╚═══════════════════════════════════════════════════════════════════╝');
console.log('\n');

const phpServer = spawn(phpExe, ['-S', `${HOST}:${PORT}`, '-t', __dirname], {
    stdio: 'inherit'
});

phpServer.on('error', (err) => {
    console.error('[VAST STUDIO] Lỗi khi chạy PHP Server:', err.message);
});

phpServer.on('close', (code) => {
    console.log(`[VAST STUDIO] Máy chủ đã dừng (code: ${code})`);
});
