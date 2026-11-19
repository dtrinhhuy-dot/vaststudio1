// ==========================================================================
// VAST STUDIO — Lightweight Static & E-Commerce Server (Node.js Built-in)
// No npm dependencies or Laragon/XAMPP required.
// Run: node server.js OR double click start.bat OR npm start
// ==========================================================================

const http = require('http');
const fs = require('fs');
const path = require('path');
const url = require('url');
const os = require('os');
const { exec } = require('child_process');

const DEFAULT_PORT = process.env.PORT || 3000;
const PUBLIC_DIR = __dirname;

const MIME_TYPES = {
    '.html': 'text/html; charset=UTF-8',
    '.php': 'text/html; charset=UTF-8',
    '.css': 'text/css; charset=UTF-8',
    '.js': 'application/javascript; charset=UTF-8',
    '.json': 'application/json; charset=UTF-8',
    '.png': 'image/png',
    '.jpg': 'image/jpeg',
    '.jpeg': 'image/jpeg',
    '.webp': 'image/webp',
    '.svg': 'image/svg+xml',
    '.ico': 'image/x-icon',
    '.woff2': 'font/woff2',
    '.woff': 'font/woff',
    '.ttf': 'font/ttf'
};

function getLocalIP() {
    try {
        const interfaces = os.networkInterfaces();
        for (const name of Object.keys(interfaces)) {
            for (const iface of interfaces[name]) {
                if (iface.family === 'IPv4' && !iface.internal) {
                    return iface.address;
                }
            }
        }
    } catch (e) {}
    return '127.0.0.1';
}

function serveFile(res, filePath, contentType) {
    fs.readFile(filePath, (err, content) => {
        if (err) {
            if (err.code === 'ENOENT') {
                res.writeHead(404, { 'Content-Type': 'text/html; charset=UTF-8' });
                res.end('<div style="text-align:center; padding:50px; font-family:sans-serif;"><h1>404 Not Found — VAST STUDIO</h1><p>Trang hoặc tài nguyên không tồn tại.</p><a href="/">Quay về Trang Chủ</a></div>', 'utf-8');
            } else {
                res.writeHead(500, { 'Content-Type': 'text/html; charset=UTF-8' });
                res.end(`<h1>500 Server Error</h1><p>${err.code}</p>`, 'utf-8');
            }
        } else {
            res.writeHead(200, { 
                'Content-Type': contentType,
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Access-Control-Allow-Origin': '*'
            });
            res.end(content, 'utf-8');
        }
    });
}

function startServer(port) {
    const server = http.createServer((req, res) => {
        const parsedUrl = url.parse(req.url);
        let pathname = decodeURIComponent(parsedUrl.pathname || '/');

        if (pathname === '/' || pathname === '') {
            pathname = fs.existsSync(path.join(PUBLIC_DIR, 'index.php')) ? '/index.php' : '/index.html';
        }

        const safePath = path.normalize(path.join(PUBLIC_DIR, pathname));

        // Security check: prevent directory traversal
        if (!safePath.startsWith(PUBLIC_DIR)) {
            res.writeHead(403, { 'Content-Type': 'text/plain; charset=UTF-8' });
            res.end('403 Forbidden');
            return;
        }

        const extname = path.extname(safePath).toLowerCase();
        const contentType = MIME_TYPES[extname] || 'application/octet-stream';

        fs.stat(safePath, (err, stats) => {
            if (err || !stats.isFile()) {
                // If requesting without extension (clean url, e.g. /checkout -> /checkout.php or /checkout.html)
                const cleanPhpPath = safePath + '.php';
                const cleanHtmlPath = safePath + '.html';
                if (fs.existsSync(cleanPhpPath)) {
                    serveFile(res, cleanPhpPath, 'text/html; charset=UTF-8');
                } else if (fs.existsSync(cleanHtmlPath)) {
                    serveFile(res, cleanHtmlPath, 'text/html; charset=UTF-8');
                } else if (!extname) {
                    const defaultIndex = fs.existsSync(path.join(PUBLIC_DIR, 'index.php')) ? 'index.php' : 'index.html';
                    serveFile(res, path.join(PUBLIC_DIR, defaultIndex), 'text/html; charset=UTF-8');
                } else {
                    res.writeHead(404, { 'Content-Type': 'text/html; charset=UTF-8' });
                    res.end('<h1>404 Not Found — VAST STUDIO</h1>', 'utf-8');
                }
            } else {
                serveFile(res, safePath, contentType);
            }
        });
    });

    server.on('error', (err) => {
        if (err.code === 'EADDRINUSE') {
            console.log(`[VAST STUDIO] Port ${port} is busy, trying port ${port + 1}...`);
            startServer(port + 1);
        } else {
            console.error('[VAST STUDIO] Server error:', err);
        }
    });

    server.listen(port, () => {
        const localIP = getLocalIP();
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
        console.log('║            MÁY CHỦ VAST STUDIO (KHÔNG CẦN LARAGON/XAMPP)          ║');
        console.log('║                                                                   ║');
        console.log(`║  ➜ Local:   http://localhost:${port}                                 ║`);
        console.log(`║  ➜ Network: http://${localIP}:${port} (Test qua Wi-Fi)              ║`);
        console.log('║                                                                   ║');
        console.log('║  • Trang chủ:   http://localhost:' + port + '/index.html                    ║');
        console.log('║  • Quản trị:    http://localhost:' + port + '/admin.html                    ║');
        console.log('║  • Thanh toán:  http://localhost:' + port + '/checkout.html                 ║');
        console.log('║  • Thành viên:  http://localhost:' + port + '/auth.html                     ║');
        console.log('║                                                                   ║');
        console.log('║  Nhấn Ctrl + C để dừng server.                                    ║');
        console.log('╚═══════════════════════════════════════════════════════════════════╝');
        console.log('\n');
    });
}

startServer(DEFAULT_PORT);
