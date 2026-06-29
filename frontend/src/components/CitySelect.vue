<template>
  <div class="city-select">
    <div class="form-group">
      <label class="form-label">{{ provinceLabel }}</label>
      <select
        :value="province"
        class="form-select"
        @change="onProvinceChange"
      >
        <option value="">{{ provincePlaceholder }}</option>
        <option v-for="r in regions" :key="r.province" :value="r.province">
          {{ r.province }}
        </option>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label">{{ cityLabel }}</label>
      <select
        :value="city"
        class="form-select"
        :disabled="!province"
        @change="onCityChange"
      >
        <option value="">{{ cityPlaceholder }}</option>
        <option v-for="c in cityOptions" :key="c" :value="c">{{ c }}</option>
      </select>
    </div>
    <div v-if="showDistrict" class="form-group">
      <label class="form-label">{{ districtLabel }}</label>
      <input
        :value="district"
        class="form-input"
        :placeholder="districtPlaceholder"
        @input="$emit('update:district', $event.target.value)"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  regions: { type: Array, default: () => [] },
  province: { type: String, default: '' },
  city: { type: String, default: '' },
  district: { type: String, default: '' },
  showDistrict: { type: Boolean, default: true },
  provinceLabel: { type: String, default: '省份/州' },
  cityLabel: { type: String, default: '城市' },
  districtLabel: { type: String, default: '区域（选填）' },
  provincePlaceholder: { type: String, default: '请选择省份' },
  cityPlaceholder: { type: String, default: '请选择城市' },
  districtPlaceholder: { type: String, default: '如：社区、街道' },
})

const emit = defineEmits(['update:province', 'update:city', 'update:district'])

const cityOptions = computed(() => {
  const region = props.regions.find(r => r.province === props.province)
  return region?.cities ?? []
})

function onProvinceChange(e) {
  const value = e.target.value
  emit('update:province', value)
  emit('update:city', '')
}

function onCityChange(e) {
  emit('update:city', e.target.value)
}
</script>

<style scoped>
.city-select {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  width: 100%;
}

@media (max-width: 480px) {
  .city-select {
    grid-template-columns: 1fr;
  }
}

.city-select .form-group:last-child {
  grid-column: 1 / -1;
}
</style>
