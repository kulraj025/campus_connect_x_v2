```php
<?php
// includes/ad_banner.php
// Usage: include 'includes/ad_banner.php'; then call adBanner('dashboard');

function adBanner(string $placement = 'dashboard'): void {

    $ads = getAds($placement, 5);

    if (empty($ads)) return;

    foreach ($ads as $ad) {
        trackAdView($ad['id']);
    }

    $id = 'adb_' . uniqid();
?>

<div class="dash-ad-wrap" id="<?= $id ?>">

    <!-- Slides -->
    <?php
    foreach ($ads as $i => $ad) {

        $tc = adTypeConfig($ad['type']);
        $hasImg = !empty($ad['image_path']);
    ?>

    <div class="dash-ad <?= $i === 0 ? 'active' : '' ?>"
         data-index="<?= $i ?>"
         style="--bg-from:<?= clean($ad['bg_from']) ?>;--bg-to:<?= clean($ad['bg_to']) ?>;">

        <!-- Animated BG -->
        <div class="dash-ad-bg"></div>
        <div class="dash-ad-orb dash-ad-orb1"></div>
        <div class="dash-ad-orb dash-ad-orb2"></div>

        <div class="dash-ad-inner">

            <?php if ($hasImg) { ?>
            <div class="dash-ad-img-col">
                <img src="<?= BASE_URL . '/' . $ad['image_path'] ?>"
                     alt="<?= clean($ad['title']) ?>">
            </div>
            <?php } ?>

            <div class="dash-ad-text">

                <div class="dash-ad-badge">
                    <?= $tc['icon'] ?>
                    <span><?= $tc['label'] ?></span>
                </div>

                <div class="dash-ad-title">
                    <?= clean($ad['title']) ?>
                </div>

                <?php if (!empty($ad['subtitle'])) { ?>
                <div class="dash-ad-sub">
                    <?= clean($ad['subtitle']) ?>
                </div>
                <?php } ?>

                <?php if (!empty($ad['body'])) { ?>
                <div class="dash-ad-body">
                    <?= clean($ad['body']) ?>
                </div>
                <?php } ?>

                <?php if (!empty($ad['cta_link'])) { ?>
                <a href="<?= clean($ad['cta_link']) ?>"
                   class="dash-ad-cta"
                   onclick="fetch('<?= BASE_URL ?>/api/track_ad.php?id=<?= $ad['id'] ?>&type=click',{method:'POST'})">

                    <?= clean($ad['cta_text'] ?? 'Learn More') ?>
                    <span>→</span>

                </a>
                <?php } ?>

            </div>
        </div>

        <!-- Progress -->
        <div class="dash-ad-prog">
            <div class="dash-ad-prog-fill"
                 id="<?= $id ?>-prog-<?= $i ?>"></div>
        </div>

    </div>

    <?php } ?>

    <!-- Controls -->
    <?php if (count($ads) > 1) { ?>

    <div class="dash-ad-controls">

        <button class="dash-ad-arrow"
                onclick="dashAdPrev('<?= $id ?>')">‹</button>

        <div class="dash-ad-dots">

            <?php foreach ($ads as $i => $ad) { ?>

            <button class="dash-ad-dot <?= $i === 0 ? 'active' : '' ?>"
                    onclick="dashAdGo('<?= $id ?>',<?= $i ?>)"></button>

            <?php } ?>

        </div>

        <button class="dash-ad-arrow"
                onclick="dashAdNext('<?= $id ?>')">›</button>

    </div>

    <?php } ?>

    <!-- Dismiss -->
    <button class="dash-ad-dismiss"
            onclick="this.closest('.dash-ad-wrap').style.display='none'"
            title="Dismiss">×</button>

</div>

<style>
.dash-ad-wrap{
    position:relative;
    border-radius:16px;
    overflow:hidden;
    margin-bottom:22px;
    box-shadow:0 8px 32px rgba(0,0,0,.12)
}

.dash-ad{
    position:absolute;
    inset:0;
    opacity:0;
    transform:translateX(40px);
    transition:opacity .6s ease,transform .6s ease;
    pointer-events:none;
    min-height:120px
}

.dash-ad.active{
    position:relative;
    opacity:1;
    transform:translateX(0);
    pointer-events:auto
}

.dash-ad.exit{
    opacity:0;
    transform:translateX(-40px)
}

.dash-ad-bg{
    position:absolute;
    inset:0;
    background:linear-gradient(
        130deg,
        var(--bg-from),
        var(--bg-to),
        var(--bg-from)
    );
    background-size:300% 300%;
    animation:dashBgShift 8s ease infinite
}

@keyframes dashBgShift{
    0%{background-position:0% 50%}
    50%{background-position:100% 50%}
    100%{background-position:0% 50%}
}

.dash-ad-orb{
    position:absolute;
    border-radius:50%;
    pointer-events:none
}

.dash-ad-orb1{
    width:220px;
    height:220px;
    top:-60px;
    right:-60px;
    background:radial-gradient(circle,rgba(255,255,255,.07) 0%,transparent 70%);
    animation:orbPulse 5s ease-in-out infinite
}

.dash-ad-orb2{
    width:140px;
    height:140px;
    bottom:-40px;
    left:20%;
    background:radial-gradient(circle,rgba(255,255,255,.05) 0%,transparent 70%);
    animation:orbPulse 7s ease-in-out infinite reverse
}

@keyframes orbPulse{
    0%,100%{
        transform:scale(1) translateY(0)
    }
    50%{
        transform:scale(1.1) translateY(-10px)
    }
}

.dash-ad-inner{
    position:relative;
    z-index:2;
    display:flex;
    align-items:center;
    gap:20px;
    padding:22px 24px
}

.dash-ad-img-col img{
    width:100px;
    height:80px;
    object-fit:cover;
    border-radius:10px;
    border:1px solid rgba(255,255,255,.15);
    box-shadow:0 4px 16px rgba(0,0,0,.2);
    flex-shrink:0;
    animation:imgFloat 4s ease-in-out infinite
}

@keyframes imgFloat{
    0%,100%{
        transform:translateY(0)
    }
    50%{
        transform:translateY(-5px)
    }
}

.dash-ad-text{
    flex:1;
    min-width:0
}

.dash-ad-badge{
    display:inline-flex;
    align-items:center;
    gap:5px;
    background:rgba(255,255,255,.12);
    border:1px solid rgba(255,255,255,.2);
    color:rgba(255,255,255,.9);
    font-size:10px;
    font-weight:700;
    padding:3px 10px;
    border-radius:99px;
    letter-spacing:.08em;
    text-transform:uppercase;
    margin-bottom:8px;
    backdrop-filter:blur(6px)
}

.dash-ad-title{
    font-family:'Syne',sans-serif;
    font-size:18px;
    font-weight:800;
    color:#fff;
    line-height:1.15;
    margin-bottom:4px
}

.dash-ad-sub{
    font-size:12px;
    font-weight:600;
    color:rgba(255,255,255,.6);
    margin-bottom:4px
}

.dash-ad-body{
    font-size:12.5px;
    color:rgba(255,255,255,.55);
    line-height:1.6;
    margin-bottom:10px;
    max-width:500px
}
</style>

<script>
(function() {

    const WID = '<?= $id ?>';
    const TOTAL = <?= count($ads) ?>;
    const DURATION = 6000;

    let cur = 0;
    let timer = null;

    function getSlides() {
        return document.querySelectorAll('#' + WID + ' .dash-ad');
    }

    function getDots() {
        return document.querySelectorAll('#' + WID + ' .dash-ad-dot');
    }

    window.dashAdGo = function(wid, n) {

        if (wid !== WID) return;

        const slides = getSlides();
        const dots = getDots();

        slides[cur].classList.remove('active');
        slides[cur].classList.add('exit');

        dots[cur]?.classList.remove('active');

        setTimeout(() => {
            slides[cur]?.classList.remove('exit');
        }, 600);

        cur = ((n % TOTAL) + TOTAL) % TOTAL;

        slides[cur].classList.add('active');
        dots[cur]?.classList.add('active');

        resetProg();
    };

    window.dashAdNext = function(wid) {
        if (wid === WID) dashAdGo(WID, cur + 1);
    };

    window.dashAdPrev = function(wid) {
        if (wid === WID) dashAdGo(WID, cur - 1);
    };

    function resetProg() {

        clearInterval(timer);

        const fill =
            document.getElementById(WID + '-prog-' + cur);

        if (!fill) return;

        fill.style.transition = 'none';
        fill.style.width = '0%';

        requestAnimationFrame(() => {

            fill.style.transition =
                'width ' + DURATION + 'ms linear';

            fill.style.width = '100%';
        });

        timer = setInterval(() => {
            dashAdGo(WID, cur + 1);
        }, DURATION);
    }

    const wrap = document.getElementById(WID);

    wrap?.addEventListener('mouseenter', () => {
        clearInterval(timer);
    });

    wrap?.addEventListener('mouseleave', () => {
        resetProg();
    });

    let tx = 0;

    wrap?.addEventListener('touchstart', e => {
        tx = e.touches[0].clientX;
    }, { passive:true });

    wrap?.addEventListener('touchend', e => {

        const d = tx - e.changedTouches[0].clientX;

        if (Math.abs(d) > 50) {
            d > 0
                ? dashAdNext(WID)
                : dashAdPrev(WID);
        }
    });

    if (TOTAL > 1) {
        resetProg();
    }

})();
</script>

<?php
}
?>
```
