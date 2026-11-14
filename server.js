// ==========================================================================
// VAST STUDIO — Lightweight Static Admin Server (Node.js Built-in)
// No npm dependencies required. Run: node server.js or npm start
// ==========================================================================

const http = require('http');
const fs = require('fs');
const path = require('path');
const url = require('url');

const DEFAULT_PORT = process.env.PORT || 3000;
const PUBLIC_DIR = __dirname;

const MIME_TYPES = {
    '.html': 'text/html; charset=UTF-8',
    '.css': 'text/css; charset=UTF-8',
    '.js': 'application/javascript; charset=UTF-8',
    '.json': 'application/json; charset=UTF-8',
    '.png': 'image/png',
    '.jpg': 'image/jpeg',
    '.jpeg': 'image/jpeg',
    '.webp': 'image/webp',
    '.svg': 'image/svg+xml',
    '.ico': 'image/x-icon'
};

function serveFile(res, filePath, contentType) {
    fs.readFile(filePath, (err, content) => {
        if (err) {
            if (err.code === 'ENOENT') {
                res.writeHead(404, { 'Content-Type': 'text/html; charset=UTF-8' });
                res.end('<h1>404 Not Found — VAST STUDIO</h1>', 'utf-8');
            } else {
                res.writeHead(500, { 'Content-Type': 'text/html; charset=UTF-8' });
                res.end(`<h1>500 Server Error</h1><p>${err.code}</p>`, 'utf-8');
            }
        } else {
            res.writeHead(200, { 
                'Content-Type': contentType,
                'Cache-Control': 'no-cache, no-store, must-revalidate'
            });
            res.end(content, 'utf-8');
        }
    });
}

function startServer(port) {
    const server = http.createServer((req, res) => {
        const parsedUrl = url.parse(req.url);
        let pathname = parsedUrl.pathname;

        if (pathname === '/' || pathname === '') {
            pathname = '/index.html';
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
                // If requested a directory or non-existent file, fallback to index.html
                serveFile(res, path.join(PUBLIC_DIR, 'index.html'), 'text/html; charset=UTF-8');
            } else {
                serveFile(res, safePath, contentType);
            }
        });
    });

    server.on('error', (err) => {
        if (err.code === 'EADDRINUSE') {
            console.log(`[VAST STUDIO] Port ${port} is in use, trying port ${port + 1}...`);
            startServer(port + 1);
        } else {
            console.error('[VAST STUDIO] Server error:', err);
        }
    });

    server.listen(port, () => {
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
        console.log('║                HỆ THỐNG QUẢN TRỊ — VAST STUDIO                    ║');
        console.log('║                                                                   ║');
        console.log(`║  ➜ Local:   http://localhost:${port}                                 ║`);
        console.log('║  ➜ Network: Sẵn sàng phục vụ giao diện quản trị bán quần áo       ║');
        console.log('║                                                                   ║');
        console.log('║  Nhấn Ctrl + C để dừng máy chủ.                                   ║');
        console.log('╚═══════════════════════════════════════════════════════════════════╝');
        console.log('\n');
    });
}

startServer(DEFAULT_PORT);

