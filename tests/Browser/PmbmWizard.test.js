import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const script = readFileSync(new URL('../../resources/js/app.js', import.meta.url), 'utf8').split("document.querySelectorAll('[data-pmbm-wizard]')")[1];
function page() {
    const element = () => ({ hidden: false, addEventListener(type, fn) { this[type] = fn; } });
    const fields = Object.fromEntries(['father_name', 'father_status', 'mother_name', 'mother_status', 'guardian_name', 'guardian_relationship', 'guardian_phone', 'guardian_type', 'special_needs_other', 'special_needs'].map(name => [name, { value: '', required: false }]));
    fields.father_status.value = 'alive';
    fields.mother_status.value = 'unknown';
    const input = { type: 'text', name: 'full_name', value: '<img src=x onerror=alert(1)>', closest: () => null };
    const review = { rows: [], replaceChildren() { this.rows = []; }, append(row) { this.rows.push(row); } };
    const steps = Array.from({ length: 8 }, () => ({ ...element(), contains: target => target === input, querySelectorAll: () => [] }));
    const controls = Object.fromEntries(['prev', 'next', 'submit'].map(name => [`[data-wizard-${name}]`, element()]));
    const form = { ...element(), elements: fields, querySelector: selector => selector === '[data-wizard-review]' ? review : controls[selector], querySelectorAll: selector => selector === '[data-wizard-step]' ? steps : selector.includes('progress') ? [] : [input] };
    vm.runInNewContext("document.querySelectorAll('[data-pmbm-wizard]')" + script, { document: { querySelectorAll: () => [form], createElement: () => ({}) } });
    return { form, fields, steps, controls, review, input };
}
test('wizard validates conditional fields and reveals invalid steps', () => {
    const { form, fields, steps, controls, input } = page();
    assert.equal(fields.father_name.required, true);
    assert.equal(fields.mother_name.required, false);
    fields.guardian_type.value = 'other';
    fields.special_needs.value = 'other';
    form.change();
    assert.equal(fields.guardian_phone.required, true);
    assert.equal(fields.special_needs_other.required, true);
    controls['[data-wizard-next]'].click();
    assert.equal(steps[0].hidden, true);
    form.invalid({ target: input });
    assert.equal(steps[0].hidden, false);
});
test('review displays entered markup as text', () => {
    const { controls, review, input } = page();
    for (let i = 0; i < 7; i++) controls['[data-wizard-next]'].click();
    assert.equal(review.rows[0].textContent, `full_name: ${input.value}`);
    assert.equal(review.rows[0].innerHTML, undefined);
});
