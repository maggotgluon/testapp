# Localization

The app uses a lightweight UI-language switcher for English and Thai. New UI should use explicit English/Thai values so the app does not need to guess language from a slash-separated sentence.

## How it works

- On first visit, the app checks the device language.
- Thai devices show Thai UI.
- English devices show English UI.
- Other languages show both English and Thai.
- The user can change this from the language dropdown in the footer.
- The selected language is saved on the device in `localStorage` using `ticketflow.uiLanguage`.

## Add or change UI text in Blade

For ordinary UI labels, use the `<x-t>` component:

```blade
<x-t en="Save event" th="บันทึกอีเวนต์" />
```

The UI language script will automatically show:

- English mode: `Save event`
- Thai mode: `บันทึกอีเวนต์`
- Both mode: `Save event / บันทึกอีเวนต์`

If Thai text is missing for a localized value, Thai mode falls back to English so the UI never loses important information.

## Add or change UI text in JavaScript or Alpine

Use `window.TicketFlowLanguage.format()` for dynamic UI messages:

```js
this.message = window.TicketFlowLanguage.format({
    en: 'Order approved.',
    th: 'อนุมัติออเดอร์แล้ว',
});
```

The old `English / ไทย` pattern is still supported as a legacy fallback while older screens are migrated, but avoid adding new slash-separated UI copy.

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

For deeper translation work or a third language, replace the lightweight switcher with a key-based translation file approach. A practical migration path:

1. Keep legacy slash-separated text working during the migration.
2. Add translation keys in `resources/lang/{locale}/ui.php`.
3. Replace high-traffic labels first with Laravel `__('ui.key')`.
4. Extend the footer language selector options and supported languages in `resources/js/app.js`.

For the current English/Thai app, `<x-t en="..." th="..." />` is the preferred UI pattern.
