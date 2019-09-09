# ClassicPress-backports

This is a Laravel web application that helps the ClassicPress developers manage
backporting changes from newer WordPress versions.

Currently it just shows which WordPress changes have been included in
ClassicPress, based on the commit history and the standard format used to
include backported changes in ClassicPress (see the
[ClassicPress contributing guidelines](https://github.com/ClassicPress/ClassicPress/blob/develop/.github/CONTRIBUTING.md#backporting-changes-from-wordpress)
for more information).

Future features may include a way to mark a change as "declined". There is some
unfinished and disabled code to this effect already.

## Setup

Put a copy of the ClassicPress repository at
`storage/app/repos/ClassicPress/ClassicPress` on the server:

```
mkdir -p storage/app/repos/ClassicPress/ClassicPress
cd storage/app/repos/ClassicPress/ClassicPress
git clone https://github.com/ClassicPress/ClassicPress.git .
```

In order to cause the app to refresh its saved data from the GitHub
repositories, you'll need to log in using your GitHub account. Permissions for
write operations in the app (including this "refresh" code) are based on a
whitelist of GitHub user IDs.
