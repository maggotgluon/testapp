# Localization

The app uses a lightweight UI-language switcher for English and Thai. It is designed around the bilingual labels already used in Blade and Alpine text.

## How it works

- On first visit, the app checks the device language.
- Thai devices show Thai UI.
- English devices show English UI.
- Other languages show both English and Thai.
- The user can change this from the language dropdown in the footer.
- The selected language is saved on the device in `localStorage` using `ticketflow.uiLanguage`.

## Add or change UI text

For ordinary UI labels, write English first, then Thai:

```blade
Save event / บันทึกอีเวนต์
```

The UI language script will automatically show:

- English mode: `Save event`
- Thai mode: `บันทึกอีเวนต์`
- Both mode: `Save event / บันทึกอีเวนต์`

This also works for many Alpine strings:

```js
this.message = 'Order approved. / อนุมัติออเดอร์แล้ว';
```

## Keep user content unchanged

Do not translate user-entered content, rich event descriptions, customer names, order notes, or uploaded/payment data. If a container should never be touched by the UI language switcher, add:

```blade
data-i18n-skip
```

Example:

```blade
<div data-i18n-skip>
    {!! $eventDescriptionHtml !!}
</div>
```

## Add a new language later

Starter translation files already exist:

- `resources/lang/en/ui.php`
- `resources/lang/th/ui.php`
- `resources/lang/both/ui.php`

For deeper translation work or a third language, replace the lightweight splitter with a key-based translation file approach. A practical migration path:

1. Keep existing `English / ไทย` text working during the migration.
2. Add translation keys in `resources/lang/{locale}/ui.php`.
3. Replace high-traffic labels first with Laravel `__('ui.key')`.
4. Extend the header selector options and supported languages in `resources/js/app.js`.

For the current English/Thai app, the bilingual label pattern is the fastest and easiest to maintain.
