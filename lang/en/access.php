<?php

return [
    'nav_label' => 'Server access',
    'title' => 'Servers by role',
    'subheading' => 'Give everyone holding a role access to the same servers.',

    /*
     * Said before anything else on the page, because this is the one feature
     * here that writes to a table Pelican owns.
     */
    'warning' => 'This works by keeping Pelican\'s own subusers up to date — the same rows you would add by hand on a server\'s Users page, which is what the server list, the permission checks and Wings all read. It only ever touches rows it created: anything you added by hand is never changed and never removed. Nobody is emailed when a role grants them a server. Taking access away also revokes their SFTP, which needs the queue worker Pelican already asks for.',

    'never' => 'Nothing has been reconciled yet. Save a mapping below and it happens at once, and again on the panel\'s timer after that.',
    'last_run' => 'Last run :ago seconds ago: :added added, :removed removed, :held in place.',
    'capped' => 'Too much at once — :pairs grants, and the limit is :max. Nothing was written. Narrow a mapping: a role with fifty people and twenty servers is a thousand grants on its own.',

    'which' => 'The mappings',
    'which_helper' => 'A role, the servers everyone holding it should reach, and what they may do there. Somebody in two roles gets everything both of them grant. Server owners and root admins are skipped — they already have more than this could give them.',
    'add' => 'Add a role',

    'role' => 'Role',
    'role_helper' => 'Everyone holding it, including anyone given it later.',
    'servers' => 'Servers',
    'servers_helper' => 'The servers they get. Removing one here takes that access away again.',

    'permissions' => 'What they may do',
    'permissions_helper' => 'Pelican\'s own subuser permissions. Leave them as they are for a sensible set: the console, the power buttons, files, backups and the activity log — and nothing that edits the server, its users, its databases or its allocations. Connect to websocket is always included, because without it the console page connects to nothing.',

    'save' => 'Save and apply',
    'saved' => 'Saved',
    'saved_body' => ':added granted, :removed taken back.',
    'save_failed' => 'Could not save',
    'save_failed_disk' => 'The list could not be written to storage. Check that storage/app belongs to the user the panel runs as.',

    'revoke' => 'Take it all back',
    'revoke_confirm' => 'Remove everything this has granted?',
    'revoke_confirm_helper' => 'Every subuser row this page created, on every server, for everybody — and their SFTP with it. Rows you added by hand are not touched. The mappings below stay, so the next save or the next timer would grant them again: empty the list first if you mean it for good.',
    'revoked' => ':count removed',
    'revoked_body' => 'Only rows this page had created. Anything added by hand is where it was.',
    'revoke_failed' => 'Could not remove them',
];
