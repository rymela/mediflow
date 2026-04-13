<?php

declare(strict_types=1);

/** Shared <head> partial for all MediFlow Mag views.
 *
 * Variables consumed (all optional, controllers should pass them):
 *   string $pageTitle  — browser <title> (default: 'MediFlow Mag')
 *   string $metaDesc   — meta description
 */

$pageTitle = isset($pageTitle) && $pageTitle !== '' ? (string)$pageTitle : 'MediFlow Mag';
$metaDesc  = isset($metaDesc)  && $metaDesc  !== '' ? (string)$metaDesc  : 'Verified medical insights, research and health news from MediFlow Clinic.';
?>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<meta name="description" content="<?= e($metaDesc) ?>"/>
<title><?= e($pageTitle) ?></title>

<!-- Tailwind CSS CDN (forms + container-queries plugins) -->
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

<!-- Google Fonts: Manrope (headlines) + Inter (body) — loaded ONCE -->
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>

<!-- Material Symbols Outlined — loaded ONCE -->
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

<!-- Shared Tailwind theme config (Material Design 3 colour tokens) -->
<script id="tailwind-config">
  tailwind.config = {
    darkMode: 'class',
    theme: {
      extend: {
        colors: {
          'primary':                    '#004d99',
          'primary-fixed':              '#d6e3ff',
          'primary-fixed-dim':          '#a9c7ff',
          'primary-container':          '#1565c0',
          'on-primary':                 '#ffffff',
          'on-primary-fixed':           '#001b3d',
          'on-primary-fixed-variant':   '#00468c',
          'on-primary-container':       '#dae5ff',
          'inverse-primary':            '#a9c7ff',
          'secondary':                  '#4a5f83',
          'secondary-fixed':            '#d6e3ff',
          'secondary-fixed-dim':        '#b2c7f1',
          'secondary-container':        '#c0d5ff',
          'on-secondary':               '#ffffff',
          'on-secondary-fixed':         '#021b3c',
          'on-secondary-fixed-variant': '#32476a',
          'on-secondary-container':     '#475c80',
          'tertiary':                   '#005851',
          'tertiary-fixed':             '#84f5e8',
          'tertiary-fixed-dim':         '#66d9cc',
          'tertiary-container':         '#00736a',
          'on-tertiary':                '#ffffff',
          'on-tertiary-fixed':          '#00201d',
          'on-tertiary-fixed-variant':  '#005049',
          'on-tertiary-container':      '#87f8ea',
          'error':                      '#ba1a1a',
          'error-container':            '#ffdad6',
          'on-error':                   '#ffffff',
          'on-error-container':         '#93000a',
          'surface':                    '#f7f9fb',
          'surface-bright':             '#f7f9fb',
          'surface-dim':                '#d8dadc',
          'surface-variant':            '#e0e3e5',
          'surface-container-lowest':   '#ffffff',
          'surface-container-low':      '#f2f4f6',
          'surface-container':          '#eceef0',
          'surface-container-high':     '#e6e8ea',
          'surface-container-highest':  '#e0e3e5',
          'surface-tint':               '#005db7',
          'on-surface':                 '#191c1e',
          'on-surface-variant':         '#424752',
          'inverse-surface':            '#2d3133',
          'inverse-on-surface':         '#eff1f3',
          'background':                 '#f7f9fb',
          'on-background':              '#191c1e',
          'outline':                    '#727783',
          'outline-variant':            '#c2c6d4',
        },
        borderRadius: {
          DEFAULT: '0.25rem',
          lg:      '0.5rem',
          xl:      '0.75rem',
          '2xl':   '1rem',
          '3xl':   '1.5rem',
          full:    '9999px',
        },
        fontFamily: {
          headline: ['Manrope', 'sans-serif'],
          body:     ['Inter',   'sans-serif'],
          label:    ['Inter',   'sans-serif'],
        },
      },
    },
  }
</script>

<!-- Global styles -->
<style>
  *, *::before, *::after { box-sizing: border-box; }
  body { font-family: 'Inter', sans-serif; background-color: #f7f9fb; -webkit-font-smoothing: antialiased; }
  h1, h2, h3, h4, h5, .font-headline { font-family: 'Manrope', sans-serif; }
  .material-symbols-outlined {
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    user-select: none;
  }
  /* Thin, subtle scrollbar */
  ::-webkit-scrollbar { width: 5px; height: 5px; }
  ::-webkit-scrollbar-track { background: transparent; }
  ::-webkit-scrollbar-thumb { background: #c2c6d4; border-radius: 9999px; }
  ::-webkit-scrollbar-thumb:hover { background: #727783; }

  /* Toast notification */
  #mf-toast {
    position: fixed; bottom: 1.5rem; left: 50%; transform: translateX(-50%) translateY(100px);
    transition: transform .3s cubic-bezier(.34,1.56,.64,1); z-index: 9999;
    pointer-events: none;
  }
  #mf-toast.show { transform: translateX(-50%) translateY(0); }

  /* Skeleton shimmer */
  .shimmer {
    background: linear-gradient(90deg, #e6e8ea 25%, #f2f4f6 50%, #e6e8ea 75%);
    background-size: 200% 100%;
    animation: shimmer 1.4s infinite;
  }
  @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

  /* Smooth page transitions */
  .page-fade { animation: fadeIn .25s ease; }
  @keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
</style>
