# RN Pagination

**One Elementor pagination widget for everything.** It works with blog and archive pages, search results, the WooCommerce shop, JetEngine Listing Grids and Elementor Posts / Loop Grids, with AJAX page loading and full style controls.

![RN Pagination](screenshots/cover.png)

![Version](https://img.shields.io/badge/version-2.0.0-7c5cff)
![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759b)
![Elementor](https://img.shields.io/badge/Elementor-required-92003b)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4)
![License](https://img.shields.io/badge/license-GPL--2.0--or--later-green)

---

## ✨ Features

- **One widget, every source**: paginate the main page query (blog, archive, search, WooCommerce shop), a **JetEngine Listing Grid** or an **Elementor Posts / Loop Grid**, or let it **auto-detect** the source.
- **AJAX page loading**: JetEngine and Elementor grids switch pages without a reload. Blog, shop and archive pages can also load pages via AJAX (optional).
- **No "Load more" needed for JetEngine**: the pager works on any Listing Grid, so you don't have to turn on JetEngine's Load More.
- **Replaces Elementor's pagination**: on Posts / Loop Grids, the native pagination is hidden automatically and this widget takes over.
- **Smart number window**: e.g. `1 2 … 7 8 [9] 10 11 … 49 50`, with configurable pages at the edges and around the current page.
- **URL sync**: the page number is kept in the URL, so refresh, the back button and shared links open the same page.
- **JetSmartFilters friendly**: re-reads the grid after filtering or any re-render, so page counts stay correct.
- **Better UX**: scrolls to the grid after a page change (with offset), dims the grid while loading, and optionally shows "Page X of Y".
- **Fully styleable**: numbers, Previous / Next buttons, dots, info text, spacing, colors (normal / hover / active), borders, radius and shadows, all from the Elementor panel.
- **Lightweight**: one small JS and one CSS file, loaded only on pages that use the widget.

---

## 📋 Requirements

| Requirement | Version |
|---|---|
| WordPress | 6.0+ |
| PHP | 7.4+ |
| Elementor | Active |
| Elementor Pro | *Optional*, needed only for Posts / Loop Grid pagination |
| JetEngine | *Optional*, needed only for Listing Grid pagination |

---

## 🚀 Installation

### Option 1: Release ZIP (recommended)

1. Go to **[Releases](../../releases)** and download `rn-pagination.zip`.
2. In WordPress, go to **Plugins → Add New → Upload Plugin**.
3. Upload the ZIP, then click **Install Now → Activate**.

### Option 2: Git clone

```bash
git clone https://github.com/Reshma62/rn-pagination.git
cp -R rn-pagination/rn-pagination /path/to/wp-content/plugins/
```

Then activate **RN Pagination** under **Plugins**.

### Option 3: "Download ZIP" button

GitHub's **Code → Download ZIP** gives you `rn-pagination-main.zip`. Unzip it, then zip **only the inner `rn-pagination` folder** and upload that ZIP to WordPress.

---

## 🛠 Usage

1. Open your page, archive or shop template in **Elementor**.
2. Search the widget panel for **"RN Pagination"** (it is in the **RN Widgets** category).
3. Drop it below your grid and choose a source.

### Paginate a JetEngine Listing Grid

1. Select the Listing Grid and go to **Advanced → CSS ID**, e.g. `my-grid`.
2. In RN Pagination, set **Source** to `JetEngine Listing Grid` and **Grid CSS ID** to `my-grid`.
3. Done. Pages now load over AJAX. If you leave the ID empty, the first grid on the page is used.

### Paginate an Elementor Posts / Loop Grid

1. In the Posts / Loop Grid widget, set **Pagination** to **Numbers** (any type).
2. Give the grid a **CSS ID** and enter it in RN Pagination with **Source** set to `Elementor Posts / Loop Grid`.
3. The grid's own pagination is hidden automatically.

### Paginate blog / archive / search / shop pages

1. Set **Source** to `Page / archive query`.
2. *(Optional)* Turn on **Load pages without reload** and set **Content area selector** to the element that holds the posts, e.g. `.elementor-widget-archive-posts`.

---

## ⚙️ Widget settings

### Content → Paginate what?

| Setting | Description |
|---|---|
| Source | `Auto detect`, `Page / archive query`, `JetEngine Listing Grid` or `Elementor Posts / Loop Grid` |
| Grid CSS ID | The CSS ID of the grid to paginate (leave empty to use the first grid on the page) |
| Load pages without reload | AJAX mode for the main query (blog, shop, archive, search) |
| Content area selector | The element that is replaced with the next page's content |

### Content → Layout

| Setting | Default | Description |
|---|---|---|
| Type | Numbers + Prev / Next | Or `Numbers only`, or `Previous / Next only` |
| Previous / Next label | Previous / Next | Button text |
| Arrows | On | Shows arrow icons on Previous / Next |
| On first / last page | Greyed out | Shows the button greyed out, or hides it |
| Pages at the edges | 1 | How many page numbers always show at the start and end |
| Pages around the current | 1 | How many page numbers show on each side of the current page |
| Show "Page X of Y" | Off | The info text template is `Page {current} of {total}` |
| Hide when there is only one page | On | Hides the pager when there's nothing to paginate |
| Scroll to the grid after page change | On | Includes a **scroll offset** (default 100px) for sticky headers |
| Keep page number in the URL | On | Supports refresh, back button and shareable links |
| Alignment | Center | Left / center / right (responsive) |

### Style tab

- **General**: space between items, typography, grid opacity while loading
- **Page numbers**: size, border radius, border, and text / background / border colors for normal, hover and active states, plus the dots color
- **Previous / Next**: typography, padding, radius, border, shadow, colors for each state, arrow size and disabled opacity
- **"Page X of Y" text**: typography and color

---

## 🔗 URL parameters

| Source | URL parameter |
|---|---|
| JetEngine Listing Grid | `?rnp_{grid-css-id}=2` |
| Elementor Posts / Loop Grid | `?e-page-{widget-id}=2` (Elementor's own parameter, so it also works on refresh) |
| Page / archive query | WordPress's normal `/page/2/` |

---

## 📁 File structure

```
rn-pagination/
├── rn-pagination.php            # Main plugin file
├── includes/
│   └── class-rnp-plugin.php     # Bootstrap: widget, assets, JetEngine & Elementor hooks
├── widgets/
│   └── class-rnp-widget.php     # "RN Pagination" Elementor widget (controls + render)
└── assets/
    ├── rn-pagination.js         # AJAX paging, URL sync, JetSmartFilters re-sync
    └── rn-pagination.css
```

---

## ❓ Troubleshooting

| Problem | Fix |
|---|---|
| Pager doesn't appear | There may be only one page. Turn off **Hide when there is only one page** to check. |
| Wrong grid is paginated | Set a unique **CSS ID** on the grid and enter the same ID in the widget. |
| Elementor grid shows two paginations | Make sure the Posts / Loop Grid's **Pagination** is set to *Numbers*, so RN Pagination can detect and hide it. |
| Page count is wrong after filtering | Update to the latest version. The pager re-reads the grid after JetSmartFilters updates it. |
| Page jumps under a sticky header | Increase **Scroll offset (px)**. |

---

## 📝 Changelog

**2.0.0**
- One universal widget for the main query, JetEngine Listing Grid and Elementor Posts / Loop Grid.
- Renamed from MP Pagination to **RN Pagination**.

---

## 👩‍💻 Author

**Reshma Khanam**, WordPress & Full-Stack Developer
🌐 [devreshmakhanam.com](https://devreshmakhanam.com) · 💼 [LinkedIn](https://www.linkedin.com/in/reshma-khanam62/) · 🐙 [GitHub](https://github.com/Reshma62)

Need a custom WordPress plugin, Elementor widget or JetEngine solution? [Get in touch](https://devreshmakhanam.com/#contact).

## 📄 License

GPL-2.0-or-later. See [LICENSE](LICENSE).
