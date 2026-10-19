        </main>
        <footer class="footer">
            © <?= date('Y') ?> 学员管理系统 · 教练端
        </footer>
    </div>
</div>

<!-- 手机端底部导航 -->
<nav class="m-navbar" id="mNavbar">
    <button class="m-nav-item m-nav-btn" id="btnView" type="button"><span class="m-nav-ico">👥</span><span class="m-nav-txt" id="navViewTxt">所有学员管理</span></button>
    <button class="m-nav-item m-nav-btn" id="btnCoach" type="button"><span class="m-nav-ico">👨‍🏫</span><span class="m-nav-txt" id="navCoachTxt">带教教练</span></button>
    <a class="m-nav-item" href="add.php"><span class="m-nav-ico">➕</span><span class="m-nav-txt">新增</span></a>
</nav>

<!-- 手机端弹层：所有学员管理（班级×状态组合筛选） -->
<div class="m-mask" id="viewMask"></div>
<aside class="m-drawer" id="viewDrawer">
    <div class="m-drawer-title">学员筛选</div>
    <ul class="m-drawer-list" id="viewList"></ul>
</aside>

<!-- 手机端弹层：带教教练 -->
<div class="m-mask" id="coachMask"></div>
<aside class="m-drawer" id="coachDrawer">
    <div class="m-drawer-title">带教教练</div>
    <ul class="m-drawer-list" id="coachList"></ul>
</aside>

<script>
// 手机端侧边栏抽屉：点击汉堡按钮展开，点击遮罩/菜单项收起
(function () {
    var toggle = document.getElementById('navToggle');
    var mask   = document.getElementById('navMask');
    var body   = document.body;
    function openNav()  { body.classList.add('nav-open'); }
    function closeNav() { body.classList.remove('nav-open'); }
    if (toggle) toggle.addEventListener('click', openNav);
    if (mask)   mask.addEventListener('click', closeNav);
    // 点击侧边栏菜单项后，在手机端自动收起
    document.querySelectorAll('.sidebar .nav-link').forEach(function (a) {
        a.addEventListener('click', function () {
            if (window.innerWidth <= 768) closeNav();
        });
    });

    // 手机端底部导航：所有学员管理 / 带教教练 弹层筛选
    var viewItems = [
        {label: '所有学员管理', value: 'all'},
        <?php foreach (CLASSES() as $c): ?>
        {label: '<?= $c ?>', value: '<?= $c ?>'},
        <?php endforeach; ?>
        <?php foreach (STATUSES() as $s): ?>
        {label: '<?= $s ?>', value: '<?= $s ?>'},
        <?php endforeach; ?>
        <?php foreach (CLASSES() as $c): foreach (STATUSES() as $s): ?>
        {label: '<?= $c ?><?= $s ?>', value: '<?= $c ?><?= $s ?>'},
        <?php endforeach; endforeach; ?>
    ];
    var coachItems = [
        {label: '全部教练', value: ''},
        <?php foreach (COACHES() as $c): ?>
        {label: '<?= $c ?>', value: '<?= $c ?>'},
        <?php endforeach; ?>
    ];
    // 当前筛选（由页面通过 body data 属性注入，若未注入则默认全部）
    var curView  = document.body.getAttribute('data-view') || '';
    var curCoach = document.body.getAttribute('data-coach') || '';

    function buildList(items, cur, box) {
        box.innerHTML = '';
        items.forEach(function (it) {
            var li = document.createElement('li');
            li.textContent = it.label;
            if (String(it.value) === String(cur)) li.className = 'active';
            li.onclick = function () {
                closeAll();
                location.href = 'index.php?view=' + encodeURIComponent(it.value) + '&coach=' + encodeURIComponent(curCoach);
            };
            box.appendChild(li);
        });
    }
    function openDrawer(el, mask) { el.classList.add('open'); mask.classList.add('show'); }
    function closeAll() {
        document.querySelectorAll('.m-drawer').forEach(function (d) { d.classList.remove('open'); });
        document.querySelectorAll('.m-mask').forEach(function (m) { m.classList.remove('show'); });
    }
    document.getElementById('btnView').onclick  = function () { openDrawer(document.getElementById('viewDrawer'),  document.getElementById('viewMask')); };
    document.getElementById('btnCoach').onclick = function () { openDrawer(document.getElementById('coachDrawer'), document.getElementById('coachMask')); };
    document.querySelectorAll('.m-mask').forEach(function (m) { m.onclick = closeAll; });

    // 高亮底部当前导航文字
    var navViewTxt  = document.getElementById('navViewTxt');
    var navCoachTxt = document.getElementById('navCoachTxt');
    viewItems.forEach(function (it) { if (String(it.value) === String(curView)) navViewTxt.textContent = it.label; });
    coachItems.forEach(function (it) { if (String(it.value) === String(curCoach)) navCoachTxt.textContent = it.label; });

    buildList(viewItems, curView,  document.getElementById('viewList'));
    buildList(coachItems, curCoach, document.getElementById('coachList'));
})();

// 手机端学员卡片：左滑露出 编辑/删除
(function () {
    var startX = 0, startY = 0, moved = false, dragging = false;
    var ACTION_W = 128;
    var active = null;

    document.addEventListener('touchstart', function (e) {
        var swipe = e.target.closest('.m-adm-swipe');
        if (!swipe) return;
        if (active && active !== swipe) active.classList.remove('open');
        active = swipe;
        dragging = true; moved = false;
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
    }, { passive: true });

    document.addEventListener('touchmove', function (e) {
        if (!dragging) return;
        var x = e.touches[0].clientX - startX;
        var y = e.touches[0].clientY - startY;
        if (!moved && Math.abs(x) > Math.abs(y) && Math.abs(x) > 8) moved = true;
        if (!moved) return;
        e.preventDefault();
        var swipe = e.target.closest('.m-adm-swipe');
        if (!swipe) return;
        var c = swipe.querySelector('.m-adm-card');
        var nx = Math.max(-ACTION_W, Math.min(0, x));
        c.style.transition = 'none';
        c.style.transform = 'translateX(' + nx + 'px)';
    }, { passive: false });

    document.addEventListener('touchend', function (e) {
        if (!dragging) return;
        dragging = false;
        var swipe = e.target.closest('.m-adm-swipe');
        if (!swipe) return;
        var c = swipe.querySelector('.m-adm-card');
        c.style.transition = '';
        c.style.transform = '';
        if (moved) {
            var dist = e.changedTouches[0].clientX - startX; // 负 = 左滑
            if (dist < -ACTION_W / 2) swipe.classList.add('open');
            else swipe.classList.remove('open');
        }
    }, { passive: true });

    // 点击其他区域收起已打开的滑动
    document.addEventListener('click', function (e) {
        if (e.target.closest('.m-adm-swipe')) return;
        document.querySelectorAll('.m-adm-swipe.open').forEach(function (s) { s.classList.remove('open'); });
    });
})();
</script>
</body>
</html>
