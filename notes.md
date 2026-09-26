# jmmenus 2.4 update – notes

Reference trees used for every core claim below:

- upstream: `e107inc/e107` `master` @ `3cd96ea259bd90b64e775bf6918c1174efbca941` (paths `e107_handlers/…`)
- lite: `Jimmi08/e107-2.4.x-Lite` `main` @ `b80ad923e544432418937475c403a4ae59b4b5b3` (paths `ehandlers/…`)

Line numbers are given as `upstream / lite`. Where only one number is given, it is the same in both.

## Layout

- The updated plugin lives in `e107_plugins/jmmenus/`. The root files are left untouched as reference.
- Not copied: root `admin_config.php`, `admin/ajax.php`, `admin/functions.php`, `admin/js/*`.
  `README.md`, `LICENSE` and `.gitignore` are repository files and stay at the root.
- `admin/admin_config.php` still uses `require_once('../../../class2.php')`. This is relative to the
  plugin folder, so it works in both `e107_plugins/` and `eplugins/`; `e_PLUGIN` is not defined before
  `class2.php` is loaded.

## 3.1 Cleanup action

- The AJAX endpoint (`admin/ajax.php` + JS) is replaced by the admin UI route `menus/clean`.
  - `CleanPage()` shows the number of affected rows and a form with `confirm` / `cancel` triggers,
    rendered with `e_form::renderForm()` (the same generic renderer core uses for its delete confirm
    screen, `e_admin_form_ui::getConfirmDelete()`, admin_ui.php:8408 / 8422).
  - `CleanConfirmTrigger()` runs the delete, `CleanCancelTrigger()` goes back to the list.
- Why this gives permission and CSRF checks:
  - `e_admin_dispatcher::checkAccess()` / `hasModeAccess()` (admin_ui.php:1193, 1228) check the mode
    `perm`. The mode now has `'perm' => 'P'`; `checkAdminPermCode('P')` (admin_ui.php:1335) maps it to
    `checkPluginAdminPerms(e_CURRENT_PLUGIN)`.
  - `e_admin_controller::dispatchObserver()` (admin_ui.php:2619) only calls `*Trigger()` methods for
    `etrigger_*` POST keys after `checkTriggerToken()` (admin_ui.php:2705) → `e107::getSession()->check(false)`.
  - The token itself is added to every same-origin POST form by `e_token_injector::process()`
    (token_injector_handler.php, called from class2.php:2362 in both trees). `e_form::token()` is
    deprecated since 2.3.10 (form_handler.php:3892), so it is not called.
- Delete uses the query builder, which exists in both trees:
  `e107::getDb()->createQueryBuilder()->delete('menus')->where('menu_location', '')->where('menu_layout', '')->execute()`.
  - `ConnectionTrait::createQueryBuilder()` Database/ConnectionTrait.php:296.
  - `QueryBuilder::delete()` 2259 / 2179, `where()` 808 / 728, `execute()` 2557 / 2477,
    `_compileDelete()` 3938 / 3782. `where(col, value)` compiles to `` `col` = :param `` with a bound value
    (`_buildPredicate()` → `ExpressionBuilder::eq()`), so the WHERE is exactly
    `menu_location = '' AND menu_layout = ''` as before.
  - The table name goes through `resolveTableName()` (ConnectionTrait.php:178) → `hasLanguage()`, the
    same table routing as the legacy `e_db_pdo::delete()` used before.
  - `e_db_pdo::execute()` (e_db_pdo_class.php:763 / 762) returns the affected row count for DELETE, or
    false on error.
  - Legacy `delete()`, `retrieve()`, `count()` are deprecated in both trees
    (`_notifyDeprecated('delete', 'Use the query builder …')`, e_db_pdo_class.php:709 / 708).
- The old code selected ids first and deleted them one by one; the new code deletes in one statement.
  The set of deleted rows is the same.
- The button stays below the list filter via `postFilterMarkup` (rendered inside the filter fieldset,
  admin_ui.php:8522 / 8536). It is now a plain link to `menus/clean`; the old markup that closed and
  reopened `<form>`/`<fieldset>` is gone. The filter form is `method='get'`, so a link inside it is valid.
  The `btn btn-danger` classes are the same classes core itself emits (`e_form::admin_button()`,
  `getDefaultButtonClassByAction()` in both trees).
- After the delete the controller redirects to `menus/list`; `e_admin_controller::redirect()`
  (admin_ui.php:2899) moves messages to the session, so the result message is shown on the list.

## 3.2 PHP 8

- `menu_class::renderMenu()` (menu_class.php:654) passes `menu_parms` through `e107::unserialize()` and
  only replaces `$parm` when the result is truthy. For an empty `menu_parms` the menu file therefore gets
  `$parm = ''`.
- `block_code_menu.php` and `shortcode_menu.php` never parsed string parms, so a non-array `$parm` is
  normalised to `array()`. The three `frontpage_*` menus keep their existing `parse_str()` for strings and
  additionally fall back to `array()` for any other non-array value.
- Captions: `varset($parms[key])`, then `isset($caption[e_LANGUAGE]) ? $caption[e_LANGUAGE] : ''`.
  No array ever reaches `tablerender()` and no string offset is read by reference.
  This is the pattern core uses for multilan menu captions (fields declared `'multilan' => true` in
  `e_menu.php`, stored as `{language: value}` by `updateParms()`):
  - `navigation/navigation_menu.php:20` (lite `eplugins/navigation/navigation_menu.php:15`):
    `isset($parm['caption'][e_LANGUAGE]) ? $parm['caption'][e_LANGUAGE] : LAN_PLUGIN_NAVIGATION_NAME`
  - `social/xurl_menu.php:8`, `tagcloud/tagcloud_menu.php:192-195`, `news/news_archive_menu.php:106`
    (upstream): same shape, falling back to the menu's own default caption.
  The fallback in core is the menu's default caption. The jmmenus menus have no default caption (an
  unconfigured menu always rendered with an empty caption), so the fallback is `''`. No new default was
  invented.
  Consequence, same as core: a caption stored as a plain string (not a language array) is no longer
  shown. Menu Manager never stores multilan fields as plain strings, and jmmenus now refuses to save one
  (see 3.3 follow-up).

## 3.3 Saving menu parms

What core Menu Manager does (identical in both trees):

- `e_menuManager::menuSaveParameters()` menumanager_class.php:1058, called at menumanager_class.php:95
  (`parms_submit`) and 1936 (`mode=parms`, AJAX save of the parms form).
  - If `$_POST['menu_parms']` is set (plugin has no `e_menu.php`, generic text input rendered at
    menumanager_class.php:806):
    `$tp->filter($_POST['menu_parms'])`, then `strip_tags()`, then
    `createQueryBuilder()->update('menus')->setTyped('menu_parms', $parms, 'escape')->where('menu_id', (int) $id)->execute()`
    (menumanager_class.php:1065-1069).
  - Otherwise (plugin has `e_menu.php`, form built by `menuParamForm()` menumanager_class.php:684 from
    `e_menu::config()`): `e107::getMenu()->updateParms($id, $_POST)`.
- `menu_class::updateParms()` menu_class.php:276:
  - loads the row with an `e_front_model` whose data field is `menu_parms => 'json'`;
  - calls `e_menu::config(menu_name without '_menu')` of the plugin in `menu_path`;
  - for every configured field present in the input: multilan fields are merged with the previously
    stored language values, other fields are set as given; keys that are not configured fields are dropped;
  - `e_front_model::save()` → `mergePostedData()` → `sanitize()` → `sanitizeValue('json')`
    (model_class.php:2995) → `e107::serialize($value, 'json')` → `json_encode(…, JSON_PRETTY_PRINT)`
    (core_functions.php:1014 / 1019); the write uses `setTyped(…, 'json')`, and `_getPDOValue()` passes
    `json` values through unchanged (ConnectionTrait.php:1892 / 1900).
- Which path applies is decided with `file_exists(e_PLUGIN.$row['menu_path']."e_menu.php")`
  (menumanager_class.php:754).

What jmmenus does now:

- The `menu_parms` field keeps its textarea but has `'data' => false`, so the admin UI model no longer
  writes it (`e_admin_ui::_setModel()` skips fields with `data === false`, admin_ui.php:7861 / 7875). Before, it
  was saved as `'str'`, i.e. through `toDB()`, which is not what Menu Manager stores.
  The edit form still shows the value because the edit model loads the whole row (`SELECT *`,
  model_class.php:1593).
- `beforeCreate()` / `beforeUpdate()` compare the posted textarea with the stored value (CRLF normalised).
  If unchanged, nothing is written, like Menu Manager, which does not touch `menu_parms` when other menu
  settings change.
- If changed:
  - plugin has `e_menu.php` (same `file_exists()` test as core): the textarea must be a JSON object;
    it is decoded and passed to `e107::getMenu()->updateParms($id, $parms)` in `afterCreate()` /
    `afterUpdate()`. Invalid JSON aborts the save with an error and keeps the posted form.
  - no `e_menu.php`: the posted string goes through the same generic Menu Manager code
    (`$tp->filter()`, `strip_tags()`, `setTyped('menu_parms', …, 'escape')`). Decision by the user:
    matching core is required and is not "adding filtering" in the sense of 3.4.
- The write happens in the `after*` hooks because `updateParms()` reads `menu_path` / `menu_name` from
  the database, so it sees the values just saved by the admin UI.
- Duplicate `options` key: in a PHP array literal the later key wins, so the effective definition was
  the second one (edit allowed). The first one (`editClass => e_UC_NOBODY`) was removed.
- Follow-up (user decision, match Menu Manager exactly):
  - Menu Manager's form posts every value as a string, so `updateParms()` stores strings. jmmenus now
    converts every scalar leaf of the decoded JSON to a string before `updateParms()`
    (`true` → `"1"`, `false`/`null` → `""`, numbers → their string form), keeping the array structure
    (multilan values stay `{language: value}`).
  - Validation, nothing is saved and an error goes to `e107::getMessage()` when:
    - the textarea is not valid JSON, or is not a JSON object (scalars and lists are refused);
    - a field that `e_menu::config()` declares `'multilan' => true` is given as a non-array. Core would
      hit `foreach($parms[$fld] as $lang => $val)` on a string (menu_class.php:321) and warn on PHP 8;
      Menu Manager's form always posts an array there.
  - The configured fields are read the same way `updateParms()` does: `e107::getAddon(rtrim(menu_path,
    '/'), 'e_menu')` (e107_class.php:3071) and `e107::callMethod($obj, 'config', menu_name without
    '_menu')` (e107_class.php:3167), both identical in the two trees. The posted `menu_path`/`menu_name`
    are used because validation runs before the admin UI saves the row.
  - `getAddon()` returns null when the plugin is not in the `e_menu_list` pref; then there are no
    configured fields to check, and `updateParms()` itself returns false (reported as
    `LAN_UPDATED_FAILED`), which is also what Menu Manager does.

## 3.4 Content filtering in upstream Menu Manager – STOPPED, reported only

- Saving, generic path (plugin without `e_menu.php`): upstream filters.
  `menumanager_class.php:1067` `$parms = $tp->filter($_POST['menu_parms']);` (default type `str` =
  `htmlspecialchars(strip_tags($input), ENT_QUOTES)`, e_parse_class.php:5738 / 5727) and
  `menumanager_class.php:1068` `$parms = strip_tags($parms);`.
- Saving, `e_menu.php` path: no content filtering. `updateParms()` → `sanitizeValue('json')` only
  JSON-encodes (model_class.php:3020, `case 'json'`), and `_getPDOValue('json', …)` returns the value
  as is.
- Rendering: no filtering. `menu_class::renderMenu()` (menu_class.php:654-667) unserializes and hands the
  array to the menu file as `$parm`.
- Per the rules, nothing was changed in 3.4. jmmenus does not add any filtering of its own; the generic
  path in 3.3 reuses the core behaviour above (user decision).

## 3.5 block_code style leak

- `e_render::setStyle()` (e_render_class.php:200) stores the style in `$eSetStyle`, which stays set for
  every later `tablerender()` (e_render_class.php:314 → `tablestyle()` reads `$this->eSetStyle`).
  `block_code_menu.php` now reads `getStyle()` (e_render_class.php:288) before and restores it after its
  own `tablerender()`, only when it changed the style.
- `setStyle()` casts to string, so an original `null` comes back as `''`. `tablestyle()` only uses the
  value through `(string)`, `!empty()` and `===` against `'default'`/`'main'`, where both behave the same.

## 3.6 Structure and language

- Dispatcher class is in `admin/admin_menu.php`, included by `admin/admin_config.php` with
  `e_PLUGIN.'jmmenus/admin/admin_menu.php'`. Core does not auto-load any `admin_menu.php`, and the Menu
  Manager scan for `*_menu.php` uses `get_files(e_PLUGIN, "_menu\.php$", 'standard', 1)`
  (menumanager_class.php:511 and 2677), i.e. one level below `e_PLUGIN`, so `admin/admin_menu.php` is not
  picked up as a front-end menu (recursion depth check in file_class.php:250 `get_files()`).
- Alias `main/edit` pointed at a mode that does not exist; it is now `menus/edit → menus/list`
  (plus `menus/clean → menus/list` so the menu entry stays highlighted).
- `e107::lan('jmmenus', true, true)` → `plugLan('jmmenus', true, true)` (e107_class.php:4432):
  `$fname === true` → `English_admin`, `$flat === true` →
  `e_PLUGIN.'jmmenus/languages/English/English_admin.php'`. Same code in both trees. In the admin area
  `includeLan()` (e107_class.php:4212) swaps in the `adminlanguage` pref; array-style files get an English
  fallback via `includeLanArray()` (e107_class.php:4269).
- `e_menu.php` loads the same file in its constructor. `e_menu::config()` is only called from Menu Manager
  and from `menu_class::updateParms()`, both admin-side.
- LAN file uses the `return [ … ];` format, like core's own language files.

## 3.7 plugin.xml

- `compatibility="2.4"`: `_fixCompat()` (plugin_class.php:1188) keeps `2.4`. Both reference trees report
  2.4.x (`e107_admin/ver.php:13` `2.4.0`, `eadmin/ver.php:13` `2.4.0.4`).
- Name/summary/description LANs are in `languages/English/English_global.php`. That is the file core
  loads for every installed plugin (`e107::_loadPluginLans()` → `plugLan($plug, 'global', true)`,
  e107_class.php:425) and the file `_detectLanGlobal()` (plugin_class.php:1148) looks for; the admin LAN
  file is only loaded on the plugin's own admin page. `plugin.xml` keeps the English texts as fallback
  (`e_plugin::getName()` uses the constant only when it is defined, plugin_class.php:920).
- `version` bumped to 2.0.0 (user decision).

## Verification

- PHP available: 8.4.19 only. `php -l` on every PHP file in `e107_plugins/jmmenus/`: no errors.
  PHP 7.4–8.3 were not available; the code uses no syntax newer than 7.4 (reasoned, not run).
- Menu harness (run): each `*_menu.php` included from a function with `$parm` set to `''`, `null`, a
  partial array, a caption with only another language, and a full `block_code` set; core stubbed
  (`e107::getRender()`, `getParser()`, `library()`, `css()`, `pref()`, `getPlugPref()`, real `varset()`),
  `E_ALL` with an error handler that records everything.
  - New files: no errors, warnings, notices or deprecations; the caption passed to `tablerender()` is
    always a string (current-language value or `''`, also for other-language-only and plain-string
    captions). Style after `block_code` is the outer style again.
  - Old root files, same harness: `TypeError: Cannot access offset of type string on string` for
    `$parm = ''` in all five menus, plus undefined-key warnings for partial arrays, and the style leaks
    (`menu` stays set after `block_code`).
- Admin harness (run): `admin/admin_config.php` executed against a stub `class2.php` (fake query builder
  that records the chain, fake message/log/menu objects, stub admin UI base classes), tests run from a
  shutdown function after the script's `exit`. Checked: the LAN load call, aliases, field list (single
  `options`), the list button, `CleanPage()` with 3 and 0 rows, `CleanConfirmTrigger()` for 3 / 0 / false
  (query chain, messages with session flag, log entry, redirect to `list`), `CleanCancelTrigger()`,
  parms unchanged (CRLF), invalid JSON, JSON scalar and JSON list (save aborted), multilan field as string for `block_code` and `shortcode` (error, save aborted, no write), scalar-to-string conversion (`7`, `12`, `true`, `null`, `false`, `1.5` → `"7"`, `"12"`, `"1"`, `""`, `""`, `"1.5"`), `e_menu.php` path → `updateParms()`, emptied
  textarea, generic path → `filter()` + `strip_tags()` + `setTyped(…, 'escape')`, create with a failing
  write, update without `menu_parms`. No PHP issues from plugin code (one deprecation came from the stub
  base class, which lacks the `public $postFilterMarkup` that the real `e_admin_ui` declares).
- NOT run: a real e107 upstream or Lite install. Permission check, CSRF token injection and check, the
  redirect, the edit form showing `menu_parms` with `'data' => false`, and the look in the Lite admin theme
  are reasoned from the core code cited above only.

## Core API evidence (definition read in both trees)

| API / constant | upstream | lite |
|---|---|---|
| `e107::getDb()` | e107_handlers/e107_class.php:1731 | ehandlers/e107_class.php:1731 |
| `e107::getMessage()` | e107_class.php:2469 | e107_class.php:2469 |
| `e107::getLog()` | e107_class.php:2107 | e107_class.php:2107 |
| `e107::getMenu()` | e107_class.php:1896 | e107_class.php:1896 |
| `e107::getParser()` | e107_class.php:1629 | e107_class.php:1629 |
| `e107::getRender()` | e107_class.php:1821 | e107_class.php:1821 |
| `e107::lan()` / `plugLan()` | e107_class.php:4606 / 4432 | e107_class.php:4606 / 4432 |
| `e107::redirect()` | e107_class.php:5066 | e107_class.php:5066 |
| `e107::getAdminUI()` | e107_class.php:3058 | e107_class.php:3058 |
| `e107::getLayouts()`, `library()`, `css()`, `pref()`, `getPlugPref()` (unchanged menu code) | e107_class.php:4007, 2523, 2859, 4635, 1476 | same lines |
| `createQueryBuilder()` | Database/ConnectionTrait.php:296 | Database/ConnectionTrait.php:296 |
| `QueryBuilder::select()` / `from()` / `where()` | Database/QueryBuilder.php:386 / 587 / 808 | Database/QueryBuilder.php:365 / 566 / 728 |
| `QueryBuilder::delete()` / `update()` / `setTyped()` | 2259 / 1970 / 2059 | 2179 / 1890 / 1979 |
| `QueryBuilder::execute()` / `count()` / `fetchRow()` | 2557 / 2805 / 2708 | 2477 / 2725 / 2628 |
| `e_db_pdo::execute()` | e_db_pdo_class.php:763 | e_db_pdo_class.php:762 |
| `menu_class::updateParms()` (`e_menu`) | menu_class.php:276 | menu_class.php:276 |
| `e107::getAddon()` / `e107::callMethod()` | e107_class.php:3071 / 3167 | e107_class.php:3071 / 3167 |
| `e_parse::filter()` | e_parse_class.php:5738 | e_parse_class.php:5727 |
| `e_parse::toHTML()` / `parseTemplate()` | e_parse_class.php:1796 / 901 | e_parse_class.php:1797 / 902 |
| `e_render::setStyle()` / `getStyle()` / `tablerender()` | e_render_class.php:200 / 288 / 314 | same lines |
| `eMessage::addSuccess/addError/addWarning/addInfo()` | message_handler.php:330 / 343 / 356 / 369 | same lines |
| `e_admin_log::add()` / `E_LOG_INFORMATIVE` | admin_log_class.php:203 / 90 | same lines |
| `class e_admin_dispatcher` / `$adminMenuAliases` / `$pageTitles` | admin_ui.php:997 / 1056 / 1077 | same lines |
| `e_admin_dispatcher::checkAccess()` / `hasModeAccess()` / `checkAdminPermCode()` | admin_ui.php:1193 / 1228 / 1335 | same lines |
| `e_admin_controller::dispatchObserver()` / `checkTriggerToken()` | admin_ui.php:2619 / 2705 | same lines |
| `e_admin_controller::redirect()` / `redirectAction()` | admin_ui.php:2899 / 2941 | same lines |
| `e_admin_controller_ui::getUI()` | admin_ui.php:4003 | admin_ui.php:4003 |
| `class e_admin_ui` / `$postFilterMarkup` | admin_ui.php:5969 / 5997 | admin_ui.php:5983 / 6011 |
| `e_admin_ui::beforeCreate/afterCreate/beforeUpdate/afterUpdate()` | admin_ui.php:7502 / 7512 / 7531 / 7542 | admin_ui.php:7516 / 7526 / 7545 / 7556 |
| `e_admin_ui::renderHelp()` | admin_ui.php:7597 | admin_ui.php:7611 |
| `e_admin_controller_ui::_manageSubmit()` (hook order) | admin_ui.php:5220 | admin_ui.php:5174 |
| `class e_admin_form_ui` | admin_ui.php:7968 | admin_ui.php:7982 |
| `e_form::renderForm()` | form_handler.php:8394 | form_handler.php:8394 |
| `varset()` | core_functions.php:43 | core_functions.php:43 |
| `getperms()` | class2.php:1330 | class2.php:1330 |
| `e_PLUGIN` / `e_ADMIN` / `e_REQUEST_SELF` / `e_UC_NOBODY` | e107_class.php:5794 / 5791 / 6059 / 5640 | same lines |
| `e_LANGUAGE` | language_class.php:696 | language_class.php:696 |
| `LAN_CANCEL` / `LAN_TITLE` | e107_languages/English/English.php:91 / 130 | elanguages/English/English.php:91 / 130 |
| `LAN_CONFDELETE` / `LAN_UPDATED_FAILED` / `LAN_NO_CHANGE` | English/admin/lan_admin.php:206 / 183 / 184 | same lines |
| `LAN_MANAGE` / `LAN_ID` / `LAN_ORDER` / `LAN_USERCLASS` / `LAN_OPTIONS` / `LAN_HELP` | lan_admin.php:157 / 285 / 216 / 271 / 175 / 273 | same lines |
| `LAN_CAPTION` / `LAN_TEMPLATE` (unchanged e_menu code) | lan_admin.php:367 / 296 | same lines |

## Open questions

1. Resolved: `version` bumped to 2.0.0.
2. Lite does not ship the `hero` and `featurebox` plugins (`eplugins/` has neither). `frontpage_hero_menu.php`,
   `frontpage_featurebox_menu.php` and the matching `e_menu.php` cases (`e107::getLayouts('hero', …)`,
   `getLayouts('featurebox', …)`) depend on them. Left as is (user decision); behaviour on Lite without
   them is UNVERIFIED.
3. Resolved: captions follow core (current language or `''`), see 3.2.
4. Resolved: scalars stored as strings, invalid JSON and non-array multilan fields refused (see 3.3).
5. Resolved: button stays as a plain link in `postFilterMarkup`.
6. Kept as core behaviour (user decision). Possible upstream issue, to be verified in a browser:
   the generic path (menumanager_class.php:1067) runs `filter()` = `htmlspecialchars(strip_tags())`
   (e_parse_class.php:5738 / 5727) on every save, and the stored value is shown again in a text input
   (menumanager_class.php:806). If the input shows the entities decoded, each save of an unchanged value
   encodes `&`, `"`, `'`, `<`, `>` once more (`&` → `&amp;` → `&amp;amp;`). jmmenus only re-saves when the
   textarea content changed, so it does not trigger this on unrelated edits.
7. Resolved: work continues on `jmmenus-2-4-update`.
