# Dev tools

## New release:

Update plugin.json and composer.json with version, commit and push.
Tag the release.
Run:
```
nvm use
npm install -g auto-changelog
auto-changelog
``
Delete the tag release
```
git tag -d tagname
```
add the changelog, commit and push.
create tag again.
push the tag.

