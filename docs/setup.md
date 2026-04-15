# 1. Initialize the repository
git init devmailer
cd devmailer

# 2. Create the directory structure
mkdir -p src mailer/dev_inbox examples docs
touch mailer/dev_inbox/.gitkeep

# 3. Add all the files (copy the content from above)
# - README.md
# - LICENSE
# - .gitignore
# - composer.json
# - src/DevMailer.php (the main code)
# - mailer/dev_inbox.php
# - mailer/dev_email_view.php
# - examples/*.php
# - docs/*.md

# 4. Stage and commit
git add .
git commit -m "Initial commit: DevMailer v1.0.0"

# 5. Add remote and push
git remote add origin https://github.com/yourusername/devmailer.git
git branch -M main
git push -u origin main

# 6. Tag the release
git tag -a v1.0.0 -m "First stable release"
git push origin v1.0.0