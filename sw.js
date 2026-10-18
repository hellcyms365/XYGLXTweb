/**
 * 学员管理系统 - Service Worker
 * 提供：静态资源缓存（离线可用）、网络优先策略
 */
const CACHE_NAME = 'student-mgr-v1';
const STATIC_ASSETS = [
    './',
    './index.php',
    './css/front.css',
    './css/style.css',
    './assets/icon-192.png',
    './assets/icon-512.png',
    './manifest.json',
];

// 安装：预缓存静态资源
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS)).then(() => self.skipWaiting())
    );
});

// 激活：清理旧缓存
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
        ).then(() => self.clients.claim())
    );
});

// 请求策略：先网络，失败回退缓存（页面/API 走网络保证数据最新；静态资源走缓存加速）
self.addEventListener('fetch', (event) => {
    const req = event.request;
    // 只处理同源 GET 请求
    if (req.method !== 'GET' || !req.url.startsWith(self.location.origin)) {
        return;
    }

    // 静态资源：缓存优先
    if (/\.(css|js|png|jpg|jpeg|svg|ico|woff2?)$/.test(req.url)) {
        event.respondWith(
            caches.match(req).then((cached) => cached || fetch(req).then((res) => {
                const clone = res.clone();
                caches.open(CACHE_NAME).then((c) => c.put(req, clone));
                return res;
            }))
        );
        return;
    }

    // 页面/接口：网络优先，失败回退缓存
    event.respondWith(
        fetch(req).then((res) => {
            // 只缓存正常响应，不缓存 login.php（避免登录页缓存导致的问题）
            if (res.ok && !req.url.includes('login.php') && !req.url.includes('logout.php')) {
                const clone = res.clone();
                caches.open(CACHE_NAME).then((c) => c.put(req, clone));
            }
            return res;
        }).catch(() => caches.match(req).then((cached) => cached || caches.match('./index.php')))
    );
});
