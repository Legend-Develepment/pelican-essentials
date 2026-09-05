/*
 * Support\Access, ported.
 *
 * This is the only part of the plugin that writes to a table Pelican owns, and
 * two of the things it decides are decisions about somebody's access:
 *
 *   - which (person, server) pairs a set of mappings asks for, and
 *   - which rows may be written or removed to make that true.
 *
 * The second is where a mistake is expensive in a way nothing else here is. A
 * wrong answer either hands somebody a server they should not reach, or deletes
 * a subuser row an administrator wrote by hand. Both are pure logic over lists,
 * which is exactly the shape a test can hold.
 */
let pass = 0;
let fail = 0;

const NEWLINE = String.fromCharCode(10);

const check = (label, got, want) => {
    if (JSON.stringify(got) === JSON.stringify(want)) { pass++; return; }
    fail++;
    console.error('  FAIL  ' + label + '\n        got  ' + JSON.stringify(got) + '\n        want ' + JSON.stringify(want));
};

/* ------------------------------------------------------------- the port -- */

const PRESET = ['websocket.connect', 'control.console', 'file.read'];

// A stand-in for SubuserPermission::cases(). The PHP reads the enum, so the
// exact list here matters less than that something is in and something is out.
const KNOWN = [
    'websocket.connect',
    'control.console', 'control.start', 'control.stop',
    'file.read', 'file.read-content', 'file.update',
    'user.create', 'user.delete',
];

function permissions(value) {
    const out = {};

    for (const permission of Array.isArray(value) ? value : []) {
        if (KNOWN.includes(permission)) { out[permission] = true; }
    }

    if (Object.keys(out).length === 0) { return PRESET; }

    out['websocket.connect'] = true;

    return Object.keys(out).sort();
}

const ids = (value) => Array.isArray(value)
    ? [...new Set(value.map((v) => parseInt(v, 10) || 0).filter((v) => v > 0))].sort((a, b) => a - b)
    : [];

const MAX = 50;
const MAX_SERVERS = 200;

function clean(rows) {
    const out = new Map();

    for (const row of (Array.isArray(rows) ? rows : []).slice(0, MAX)) {
        if (row === null || typeof row !== 'object') { continue; }

        const role = parseInt(row.role, 10) || 0;

        if (role <= 0) { continue; }

        let servers = ids(row.servers);

        if (servers.length === 0) { continue; }

        let perms;

        if (out.has(role)) {
            servers = [...new Set([...out.get(role).servers, ...servers])];
            perms = [...out.get(role).permissions, ...permissions(row.permissions)];
        } else {
            perms = permissions(row.permissions);
        }

        out.set(role, {
            role,
            servers: servers.slice(0, MAX_SERVERS),
            permissions: [...new Set(perms)],
        });
    }

    return [...out.values()];
}

/*
 * desired(): every pair the mappings ask for.
 *
 * owners maps serverId -> ownerId, and a server missing from it has been
 * deleted. members maps roleId -> userIds.
 */
function desired(rows, owners, members, roots) {
    const out = {};

    for (const row of rows) {
        for (const serverId of row.servers) {
            if (!(serverId in owners)) { continue; }

            for (const userId of members[row.role] || []) {
                if (owners[serverId] === userId || roots.includes(userId)) { continue; }

                const key = userId + ':' + serverId;

                out[key] = key in out
                    ? [...new Set([...out[key], ...row.permissions])]
                    : [...row.permissions];
            }
        }
    }

    for (const key of Object.keys(out)) { out[key] = out[key].sort(); }

    return out;
}

/*
 * apply(): what to write.
 *
 * existing maps "u:s" -> permissions[] for rows already in the table; managed
 * is the set of pairs this plugin created.
 */
function apply(want, existing, managed) {
    const insert = [];
    const update = [];
    const remove = [];
    let left = 0;

    const owned = new Set(managed);

    for (const [key, perms] of Object.entries(want)) {
        if (!(key in existing)) {
            insert.push(key);
            owned.add(key);
            continue;
        }

        // A row that was not made here is left alone entirely: not changed, and
        // not adopted.
        if (!owned.has(key)) { left++; continue; }

        if (JSON.stringify([...existing[key]].sort()) !== JSON.stringify(perms)) {
            update.push(key);
        }
    }

    for (const key of [...owned]) {
        if (key in want) { continue; }

        owned.delete(key);

        if (key in existing) { remove.push(key); }
    }

    return { insert: insert.sort(), update: update.sort(), remove: remove.sort(), left, managed: [...owned].sort() };
}

console.log('server access by role\n');

/* ------------------------------------------------------- the permissions -- */

check('nothing given falls back to the preset', permissions([]), PRESET);
check('and so does nothing recognised', permissions(['made.up']), PRESET);
check('not an array', permissions('control.console'), PRESET);

// Without it the console page connects to nothing, and everything else is read
// through that socket.
check('the socket is always in', permissions(['file.read']), ['file.read', 'websocket.connect']);
check('an unknown one is dropped',
    permissions(['file.read', 'made.up']), ['file.read', 'websocket.connect']);
check('duplicates collapse',
    permissions(['file.read', 'file.read']), ['file.read', 'websocket.connect']);
check('sorted, so two equal sets compare equal',
    permissions(['user.create', 'control.console']),
    ['control.console', 'user.create', 'websocket.connect']);

/* ---------------------------------------------------------- the mappings -- */

check('an empty list', clean([]), []);
check('a role with no servers is not a mapping', clean([{ role: 1, servers: [] }]), []);
check('a mapping with no role', clean([{ role: 0, servers: [3] }]), []);
check('not a row at all', clean(['nonsense']), []);

check('one mapping', clean([{ role: 1, servers: [3, 2], permissions: ['file.read'] }]),
    [{ role: 1, servers: [2, 3], permissions: ['file.read', 'websocket.connect'] }]);

check('server ids are ids', clean([{ role: 1, servers: ['2', 0, -1, 2] }])[0].servers, [2]);

/*
 * Two rows for one role are merged rather than both kept: they would both be
 * applied anyway, so showing them apart would show a list that does not say
 * what is granted.
 */
{
    const merged = clean([
        { role: 1, servers: [2], permissions: ['file.read'] },
        { role: 1, servers: [3], permissions: ['control.console'] },
    ]);

    check('two rows for one role become one', merged.length, 1);
    check('with both sets of servers', merged[0].servers, [2, 3]);
    check('and both sets of permissions', merged[0].permissions.sort(),
        ['control.console', 'file.read', 'websocket.connect']);
}

check('two roles stay two', clean([
    { role: 1, servers: [2] },
    { role: 2, servers: [3] },
]).length, 2);

/* ------------------------------------------------------------ the pairs -- */

const R = [{ role: 1, servers: [10, 11], permissions: ['file.read'] }];
const OWNERS = { 10: 99, 11: 99 };

check('two people, two servers, four pairs',
    Object.keys(desired(R, OWNERS, { 1: [5, 6] }, [])).sort(),
    ['5:10', '5:11', '6:10', '6:11']);

check('nobody in the role', desired(R, OWNERS, { 1: [] }, []), {});
check('a role nobody mapped', desired(R, OWNERS, { 2: [5] }, []), {});

/*
 * The owner already has everything, and Pelican deletes an owner's subuser row
 * on that server's next save - so a row for one would be written and removed
 * for ever.
 */
check('the owner is skipped',
    Object.keys(desired(R, { 10: 5, 11: 99 }, { 1: [5] }, [])), ['5:11']);

check('a root admin is skipped', desired(R, OWNERS, { 1: [5] }, [5]), {});

// A server deleted since the mapping was written is not in the owners map.
check('a server that no longer exists',
    Object.keys(desired(R, { 10: 99 }, { 1: [5] }, [])), ['5:10']);

/*
 * Two roles reaching the same server give the union of what they grant, which
 * is the only answer that does not make the order of the list matter.
 */
{
    const two = [
        { role: 1, servers: [10], permissions: ['file.read'] },
        { role: 2, servers: [10], permissions: ['control.console'] },
    ];

    check('two roles, one server, the union',
        desired(two, OWNERS, { 1: [5], 2: [5] }, [])['5:10'],
        ['control.console', 'file.read']);

    check('and the other way round is the same',
        desired(two.slice().reverse(), OWNERS, { 1: [5], 2: [5] }, [])['5:10'],
        ['control.console', 'file.read']);
}

/* ---------------------------------------------------------- what to write */

const P = ['file.read', 'websocket.connect'];

check('a pair that does not exist is inserted',
    apply({ '5:10': P }, {}, []).insert, ['5:10']);
check('and becomes managed',
    apply({ '5:10': P }, {}, []).managed, ['5:10']);

check('a managed pair that still applies is left as it is',
    apply({ '5:10': P }, { '5:10': P }, ['5:10']),
    { insert: [], update: [], remove: [], left: 0, managed: ['5:10'] });

check('a managed pair whose permissions changed is updated',
    apply({ '5:10': P }, { '5:10': ['file.read'] }, ['5:10']).update, ['5:10']);

check('a managed pair no longer wanted is removed',
    apply({}, { '5:10': P }, ['5:10']).remove, ['5:10']);
check('and stops being managed', apply({}, { '5:10': P }, ['5:10']).managed, []);

/*
 * The rule the whole feature rests on. A row somebody wrote by hand on
 * Pelican's own Users page is never changed and never removed - not even when a
 * mapping happens to want the same pair.
 */
check('a hand-made row is not adopted',
    apply({ '5:10': P }, { '5:10': ['control.console'] }, []),
    { insert: [], update: [], remove: [], left: 1, managed: [] });

check('a hand-made row is never removed',
    apply({}, { '5:10': P }, []).remove, []);

check('a hand-made row is never given the mapping\'s permissions',
    apply({ '5:10': ['file.read', 'user.delete', 'websocket.connect'] }, { '5:10': ['control.console'] }, []).update,
    []);

/*
 * A managed pair whose row has already gone - removed by hand on Pelican's page
 * - is dropped from the index and nothing else. Trying to delete it again would
 * be a query for a row that is not there.
 */
{
    const result = apply({}, {}, ['5:10']);

    check('a managed row already gone is not deleted twice', result.remove, []);
    check('but it does leave the index', result.managed, []);
}

check('several at once',
    apply(
        { '5:10': P, '6:10': P, '7:10': P },
        { '6:10': P, '8:10': P },
        ['6:10', '8:10'],
    ),
    { insert: ['5:10', '7:10'], update: [], remove: ['8:10'], left: 0, managed: ['5:10', '6:10', '7:10'] });

console.log(NEWLINE + 'server access by role: ' + pass + ' passed, ' + fail + ' failed');
process.exit(fail ? 1 : 0);
