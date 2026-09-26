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
- Captions: `varset($parms[key])` first, then the `[e_LANGUAGE]` entry if the value is an array that has
  it. This keeps the old result (language value if present, otherwise the raw value) without reading a
  string offset by reference.

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
- Differences that remain (not changed, see open questions):
  - JSON numbers/booleans are stored as such; Menu Manager's form always posts strings.
  - A multilan field given as a plain string in the JSON reaches `foreach($parms[$fld] …)` in
    `updateParms()` (menu_class.php:321), which warns on PHP 8. Menu Manager's form always posts an array.

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
