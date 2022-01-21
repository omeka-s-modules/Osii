# Omeka S Item Importer

An [Omeka S](https://omeka.org/s/) module for importing items from other Omeka S installations.

This module allows administrators to import items from other Omeka S installations. It will also import any media and item sets related to those items, unless configured not to do so. Essentially, it performs a one-way synchronization from a remote Omeka S installation to the local installation.

The import is not a two-way synchronization, so changes made to the local items are not pushed to the remote items. Note, also, that the module does not preserve local changes after subsequent imports. Imported items will always reflect their state on the remote installation at the time of the snapshot. Do not run a subsequent import if you need to preserve local changes. This does not apply to site and block assignments, which are preserved.

# Copyright

Omeka S Item Importer is Copyright © 2021-present Corporation for Digital Scholarship, Vienna, Virginia, USA http://digitalscholar.org

The Corporation for Digital Scholarship distributes the Omeka source code
under the GNU General Public License, version 3 (GPLv3). The full text
of this license is given in the license file.

The Omeka name is a registered trademark of the Corporation for Digital Scholarship.

Third-party copyright in this distribution is noted where applicable.

All rights not expressly granted are reserved.
