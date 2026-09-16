<?php
$images = [];
$allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

foreach (scandir(__DIR__) as $file) {
    if ($file === '.' || $file === '..') {
        continue;
    }

    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    if (in_array($extension, $allowed, true) && is_file(__DIR__ . '/' . $file)) {
        $images[] = $file;
    }
}

// Neueste Dateien zuerst.
usort($images, static function ($a, $b) {
    return filemtime(__DIR__ . '/' . $b) <=> filemtime(__DIR__ . '/' . $a);
});

$count = count($images);
$currentIndex = 0;

if ($count > 0 && isset($_GET['pid']) && is_numeric($_GET['pid'])) {
    $requested = (int) $_GET['pid'];
    if ($requested >= 1 && $requested <= $count) {
        $currentIndex = $requested - 1;
    }
}

$currentPid = $count > 0 ? $currentIndex + 1 : 0;
$prevPid = $count > 0 ? ($currentIndex === 0 ? $count : $currentIndex) : 0;
$nextPid = $count > 0 ? ($currentIndex === $count - 1 ? 1 : $currentIndex + 2) : 0;

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function pidUrl(int $pid): string {
    return '?pid=' . $pid;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lincoln – The King</title>
    <style>
        body {
            margin: 0;
            padding: 30px;
            background: #111;
            color: #eee;
            font-family: Arial, sans-serif;
        }

        h1 {
            text-align: center;
            font-weight: normal;
            letter-spacing: 4px;
            margin-bottom: 10px;
        }

        .subtitle {
            text-align: center;
            color: #aaa;
            margin-bottom: 35px;
        }

        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            max-width: 1400px;
            margin: auto;
        }

        .gallery a {
            display: block;
        }

        .gallery img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            display: block;
            border-radius: 4px;
            transition: transform 0.25s ease;
        }

        .gallery a:hover img {
            transform: scale(1.03);
        }

        .empty {
            text-align: center;
            color: #888;
            margin-top: 60px;
            grid-column: 1 / -1;
        }

        /* Großansicht */
        .lightbox {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.94);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 70px 80px 60px;
            box-sizing: border-box;
        }

        .lightbox.open {
            display: flex;
        }

        .lightbox-image {
            max-width: 90vw;
            max-height: 82vh;
            width: auto;
            height: auto;
            object-fit: contain;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.7);
        }

        .close,
        .prev,
        .next {
            position: fixed;
            color: #fff;
            background: rgba(0, 0, 0, 0.48);
            border: 0;
            cursor: pointer;
            z-index: 10000;
            text-decoration: none;
            user-select: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .close {
            top: 18px;
            right: 24px;
            font-size: 34px;
            line-height: 1;
            width: 48px;
            height: 48px;
        }

        .prev,
        .next {
            top: 50%;
            transform: translateY(-50%);
            font-size: 48px;
            width: 64px;
            height: 90px;
        }

        .prev {
            left: 18px;
        }

        .next {
            right: 18px;
        }

        .counter {
            position: fixed;
            left: 50%;
            bottom: 18px;
            transform: translateX(-50%);
            color: #ccc;
            font-size: 14px;
            background: rgba(0, 0, 0, 0.48);
            padding: 7px 12px;
            border-radius: 14px;
            z-index: 10000;
        }

        /* Normale Links für Browser/Webzugriff */
        .normal-nav {
            position: fixed;
            left: 50%;
            bottom: 52px;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            z-index: 10001;
        }

        .normal-nav a {
            color: #fff;
            text-decoration: none;
            background: rgba(0, 0, 0, 0.55);
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 13px;
        }

        @media (max-width: 700px) {
            body {
                padding: 15px;
            }

            .gallery img {
                height: 210px;
            }

            .prev,
            .next {
                width: 52px;
                height: 76px;
                font-size: 38px;
            }

            .prev {
                left: 8px;
            }

            .next {
                right: 8px;
            }

            .lightbox {
                padding-left: 60px;
                padding-right: 60px;
            }
        }
    </style>
</head>
<body>

<h1>LINCOLN</h1>
<div class="subtitle">Continental Mark V · The King</div>

<div class="gallery">
<?php if ($count > 0): ?>
    <?php foreach ($images as $index => $image): ?>
        <a href="<?php echo h(pidUrl($index + 1)); ?>"
           data-pid="<?php echo $index + 1; ?>"
           onclick="openLightbox(<?php echo $index; ?>); return false;">
            <img src="<?php echo h($image); ?>" alt="Lincoln Continental Mark V" loading="lazy">
        </a>
    <?php endforeach; ?>
<?php else: ?>
    <div class="empty">Noch keine Bilder im Verzeichnis.</div>
<?php endif; ?>
</div>

<?php if ($count > 0): ?>
<div class="lightbox" id="lightbox" onclick="backgroundClose(event)">
    <a class="close" href="<?php echo h(pidUrl($currentPid)); ?>" onclick="closeLightbox(); return false;" aria-label="Schließen">×</a>

    <!-- Diese normalen Links bleiben auch ohne JavaScript verfügbar. -->
    <a class="prev" id="prevLink" href="<?php echo h(pidUrl($prevPid)); ?>" aria-label="Vorheriges Bild">‹</a>

    <img class="lightbox-image" id="lightboxImage" src="<?php echo h($images[$currentIndex]); ?>" alt="Lincoln Continental Mark V">

    <a class="next" id="nextLink" href="<?php echo h(pidUrl($nextPid)); ?>" aria-label="Nächstes Bild">›</a>

    <div class="counter" id="counter">Bild <?php echo $currentPid; ?> von <?php echo $count; ?></div>

    <div class="normal-nav">
        <a id="normalPrev" href="<?php echo h(pidUrl($prevPid)); ?>">← vorheriges Bild</a>
        <a id="normalNext" href="<?php echo h(pidUrl($nextPid)); ?>">nächstes Bild →</a>
    </div>
</div>
<?php endif; ?>

<script>
const images = <?php echo json_encode($images, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
const imageCount = images.length;
let currentIndex = <?php echo (int) $currentIndex; ?>;

function pidForIndex(index) {
    return index + 1;
}

function urlForIndex(index) {
    return '?pid=' + pidForIndex(index);
}

function openLightbox(index) {
    if (!imageCount) return;

    currentIndex = index;
    updateLightbox();
    document.getElementById('lightbox').classList.add('open');
    document.body.style.overflow = 'hidden';
    history.replaceState(null, '', urlForIndex(currentIndex));
}

function closeLightbox() {
    document.getElementById('lightbox').classList.remove('open');
    document.body.style.overflow = '';
    history.replaceState(null, '', '?');
}

function updateLightbox() {
    const image = document.getElementById('lightboxImage');
    const counter = document.getElementById('counter');
    const prevLink = document.getElementById('prevLink');
    const nextLink = document.getElementById('nextLink');
    const normalPrev = document.getElementById('normalPrev');
    const normalNext = document.getElementById('normalNext');

    image.src = images[currentIndex];
    counter.textContent = 'Bild ' + (currentIndex + 1) + ' von ' + imageCount;

    const prevIndex = currentIndex === 0 ? imageCount - 1 : currentIndex - 1;
    const nextIndex = currentIndex === imageCount - 1 ? 0 : currentIndex + 1;

    prevLink.href = urlForIndex(prevIndex);
    nextLink.href = urlForIndex(nextIndex);
    normalPrev.href = urlForIndex(prevIndex);
    normalNext.href = urlForIndex(nextIndex);

    history.replaceState(null, '', urlForIndex(currentIndex));
}

function changeImage(direction) {
    if (!imageCount) return;

    currentIndex += direction;

    if (currentIndex < 0) {
        currentIndex = imageCount - 1;
    } else if (currentIndex >= imageCount) {
        currentIndex = 0;
    }

    updateLightbox();
}

function backgroundClose(event) {
    if (event.target.id === 'lightbox') {
        closeLightbox();
    }
}

document.addEventListener('keydown', function (event) {
    const lightbox = document.getElementById('lightbox');
    if (!lightbox || !lightbox.classList.contains('open')) return;

    if (event.key === 'Escape') {
        closeLightbox();
    } else if (event.key === 'ArrowLeft') {
        event.preventDefault();
        changeImage(-1);
    } else if (event.key === 'ArrowRight') {
        event.preventDefault();
        changeImage(1);
    }
});
</script>

</body>
</html>
