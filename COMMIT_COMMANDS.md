# Git Commit Commands

```bash
# 1. Vite setup
git add package.json package-lock.json vite.config.js postcss.config.js tailwind.config.js
git commit -m "feat: add Vite build system with Tailwind CSS"

# 2. Frontend assets
git add resources/css/app.css resources/js/app.js resources/js/highlight.js
git commit -m "feat: add base frontend assets and highlight.js"

# 3. Comparison charts
git add resources/js/compare.js
git commit -m "feat: implement channel comparison charts with Chart.js"

# 4. Statistics charts
git add resources/js/statistics.js
git commit -m "feat: add statistics page chart rendering"

# 5. Home page update
git add resources/views/home.blade.php
git commit -m "feat: add channel comparison link with URL params"

# 6. Statistics view fix
git add resources/views/statistics.blade.php
git commit -m "fix: change unique_users to active_users field"

# 7. Architecture view
git add resources/views/architecture.blade.php
git commit -m "fix: standardize code block syntax highlighting"

# 8. Changelog view
git add resources/views/changelog.blade.php
git commit -m "chore: minor changelog formatting updates"

# 9. Header/footer partials
git add resources/views/partials/header.blade.php resources/views/partials/footer.blade.php
git commit -m "refactor: extract header/footer partials and add exclusion link"

# 10. Exclusion request
git add resources/views/exclusion-request.blade.php .github/ISSUE_TEMPLATE/channel-exclusion-request.md
git commit -m "feat: add channel exclusion request system"

# 11. Blocked channels
git add app/Http/Middleware/CheckBlockedChannel.php config/telegram.php
git commit -m "feat: implement blocked channels via environment variable"

# 12. Routes cleanup
git add routes/web.php routes/api.php
git commit -m "feat: update routes and remove test/share endpoints"

# 13. Channel info
git add app/Services/Telegram/MadelineProtoApiClient.php
git commit -m "feat: add total participants and message count to channel info"

# 14. Request validation
git add app/Http/Requests/ShowStatisticsRequest.php
git commit -m "fix: add blocked channel validation to statistics request"

# 15. Dev script
git add start-dev.sh
git commit -m "chore: add dev server startup script"

# 16. Documentation
git add ASSETS_BUILD.md
git commit -m "docs: document Vite build configuration"

# 17. Code style
git add -u
git commit -m "style: apply Laravel Pint code formatting"
```