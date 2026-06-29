<template>
  <input
    :value="displayValue"
    type="tel"
    inputmode="numeric"
    autocomplete="tel"
    :class="inputClass"
    :placeholder="placeholder"
    maxlength="12"
    @input="onInput"
    @blur="onBlur"
  />
</template>

<script setup>
import { computed } from 'vue'
import { formatPhoneAsYouType, normalizeVenezuelaPhone } from '@/utils/phone'

const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: '0412-0000000' },
  inputClass: { type: String, default: 'form-input' },
})

const emit = defineEmits(['update:modelValue'])

const displayValue = computed(() => formatPhoneAsYouType(props.modelValue))

function onInput(e) {
  emit('update:modelValue', formatPhoneAsYouType(e.target.value))
}

function onBlur() {
  const normalized = normalizeVenezuelaPhone(props.modelValue)
  if (normalized) {
    emit('update:modelValue', normalized)
  }
}
</script>
