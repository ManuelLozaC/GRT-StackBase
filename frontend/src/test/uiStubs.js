import { defineComponent, h } from 'vue';

export const ButtonStub = defineComponent({
    name: 'ButtonStub',
    props: {
        label: { type: String, default: '' },
        disabled: { type: Boolean, default: false },
        loading: { type: Boolean, default: false }
    },
    emits: ['click'],
    template: `<button :disabled="disabled || loading" @click="$emit('click', $event)">{{ label }}<slot /></button>`
});

export const DialogStub = defineComponent({
    name: 'DialogStub',
    props: {
        visible: { type: Boolean, default: false },
        header: { type: String, default: '' }
    },
    emits: ['update:visible'],
    template: `
        <section v-if="visible" data-dialog>
            <header>{{ header }}</header>
            <div><slot /></div>
            <footer><slot name="footer" /></footer>
        </section>
    `
});

export const InputTextStub = defineComponent({
    name: 'InputTextStub',
    props: {
        modelValue: { type: [String, Number], default: '' },
        disabled: { type: Boolean, default: false },
        type: { type: String, default: 'text' }
    },
    emits: ['update:modelValue'],
    template: `<input :type="type" :disabled="disabled" :value="modelValue ?? ''" @input="$emit('update:modelValue', $event.target.value)" />`
});

export const PasswordStub = defineComponent({
    name: 'PasswordStub',
    props: {
        modelValue: { type: String, default: '' },
        disabled: { type: Boolean, default: false }
    },
    emits: ['update:modelValue'],
    template: `<input type="password" :disabled="disabled" :value="modelValue ?? ''" @input="$emit('update:modelValue', $event.target.value)" />`
});

export const CheckboxStub = defineComponent({
    name: 'CheckboxStub',
    props: {
        modelValue: { type: [Boolean, Array], default: false },
        binary: { type: Boolean, default: false },
        value: { type: [String, Number], default: null },
        disabled: { type: Boolean, default: false }
    },
    emits: ['update:modelValue'],
    methods: {
        onChange(event) {
            if (this.binary) {
                this.$emit('update:modelValue', event.target.checked);
                return;
            }

            const current = Array.isArray(this.modelValue) ? [...this.modelValue] : [];

            if (event.target.checked) {
                current.push(this.value);
            } else {
                const index = current.findIndex((item) => item === this.value);

                if (index >= 0) {
                    current.splice(index, 1);
                }
            }

            this.$emit('update:modelValue', current);
        }
    },
    template: `<input type="checkbox" :disabled="disabled" :checked="binary ? modelValue : (Array.isArray(modelValue) && modelValue.includes(value))" @change="onChange" />`
});

export const ToggleSwitchStub = defineComponent({
    name: 'ToggleSwitchStub',
    props: {
        modelValue: { type: Boolean, default: false }
    },
    emits: ['update:modelValue'],
    template: `<input type="checkbox" :checked="modelValue" @change="$emit('update:modelValue', $event.target.checked)" />`
});

export const SelectStub = defineComponent({
    name: 'SelectStub',
    props: {
        modelValue: { type: [String, Number, null], default: '' },
        options: { type: Array, default: () => [] },
        optionLabel: { type: String, default: 'label' },
        optionValue: { type: String, default: 'value' },
        disabled: { type: Boolean, default: false }
    },
    emits: ['update:modelValue'],
    methods: {
        resolveOptionValue(option) {
            return this.optionValue ? option?.[this.optionValue] : option;
        },
        resolveOptionLabel(option) {
            return this.optionLabel ? option?.[this.optionLabel] : option;
        },
        onChange(event) {
            const rawValue = event.target.value;
            const matchedOption = this.options.find((option) => String(this.resolveOptionValue(option)) === rawValue);
            this.$emit('update:modelValue', matchedOption ? this.resolveOptionValue(matchedOption) : rawValue);
        }
    },
    template: `
        <select :disabled="disabled" :value="modelValue ?? ''" @change="onChange">
            <option value="">--</option>
            <option v-for="option in options" :key="String(resolveOptionValue(option))" :value="String(resolveOptionValue(option))">
                {{ resolveOptionLabel(option) }}
            </option>
        </select>
    `
});

export const MultiSelectStub = defineComponent({
    name: 'MultiSelectStub',
    props: {
        modelValue: { type: Array, default: () => [] },
        options: { type: Array, default: () => [] },
        optionLabel: { type: String, default: 'label' },
        optionValue: { type: String, default: 'value' }
    },
    emits: ['update:modelValue'],
    methods: {
        resolveOptionValue(option) {
            return this.optionValue ? option?.[this.optionValue] : option;
        },
        resolveOptionLabel(option) {
            return this.optionLabel ? option?.[this.optionLabel] : option;
        },
        onChange(event) {
            const selectedValues = Array.from(event.target.selectedOptions).map((option) => option.value);
            const resolved = this.options.filter((option) => selectedValues.includes(String(this.resolveOptionValue(option)))).map((option) => this.resolveOptionValue(option));

            this.$emit('update:modelValue', resolved);
        }
    },
    template: `
        <select multiple :value="modelValue.map((item) => String(item))" @change="onChange">
            <option v-for="option in options" :key="String(resolveOptionValue(option))" :value="String(resolveOptionValue(option))">
                {{ resolveOptionLabel(option) }}
            </option>
        </select>
    `
});

export const MessageStub = defineComponent({
    name: 'MessageStub',
    template: `<div data-message><slot /></div>`
});

export const TagStub = defineComponent({
    name: 'TagStub',
    props: {
        value: { type: String, default: '' }
    },
    template: `<span data-tag>{{ value }}<slot /></span>`
});

export const RouterLinkStub = defineComponent({
    name: 'RouterLinkStub',
    props: {
        to: { type: [String, Object], default: '/' }
    },
    template: `<a :href="typeof to === 'string' ? to : '/'"><slot /></a>`
});

export const DividerStub = defineComponent({
    name: 'DividerStub',
    template: `<hr />`
});

export const DataTableStub = defineComponent({
    name: 'DataTableStub',
    template: `<div data-datatable><slot /></div>`
});

export const ColumnStub = defineComponent({
    name: 'ColumnStub',
    render() {
        return h('div');
    }
});

export const StateEmptyStub = defineComponent({
    name: 'StateEmptyStub',
    template: `<div data-empty><slot /></div>`
});

export const StateSkeletonStub = defineComponent({
    name: 'StateSkeletonStub',
    template: `<div data-skeleton><slot /></div>`
});

export function primeVueStubs() {
    return {
        Button: ButtonStub,
        Dialog: DialogStub,
        InputText: InputTextStub,
        Password: PasswordStub,
        Checkbox: CheckboxStub,
        ToggleSwitch: ToggleSwitchStub,
        Select: SelectStub,
        MultiSelect: MultiSelectStub,
        Message: MessageStub,
        Tag: TagStub,
        Divider: DividerStub,
        DataTable: DataTableStub,
        Column: ColumnStub,
        RouterLink: RouterLinkStub,
        StateEmpty: StateEmptyStub,
        StateSkeleton: StateSkeletonStub
    };
}
