<template>
  <section class="regions-card card">
    <div class="section-head">
      <h3>省/城市管理</h3>
      <p>发布信息时的地区选择，已预置委内瑞拉各州及主要城市，可自行增删改</p>
    </div>

    <div class="regions-list">
      <div v-for="(region, pIndex) in regions" :key="pIndex" class="region-block">
        <div class="region-head">
          <input
            v-model="region.province"
            class="form-input province-input"
            maxlength="80"
            placeholder="省份/州名称"
          />
          <button
            type="button"
            class="btn-xs ghost"
            :disabled="regions.length <= 1"
            @click="removeProvince(pIndex)"
          >
            删除省份
          </button>
        </div>

        <div class="cities-list">
          <div v-for="(city, cIndex) in region.cities" :key="cIndex" class="city-row">
            <input
              v-model="region.cities[cIndex]"
              class="form-input"
              maxlength="80"
              placeholder="城市名称"
            />
            <button
              type="button"
              class="btn-xs ghost"
              :disabled="region.cities.length <= 1"
              @click="removeCity(pIndex, cIndex)"
            >
              删除
            </button>
          </div>
        </div>

        <div class="city-add">
          <input
            v-model="newCityByProvince[pIndex]"
            class="form-input"
            maxlength="80"
            placeholder="添加城市"
            @keyup.enter="addCity(pIndex)"
          />
          <button type="button" class="btn btn-primary btn-sm" @click="addCity(pIndex)">添加城市</button>
        </div>
      </div>
    </div>

    <div class="province-add">
      <input
        v-model="newProvince"
        class="form-input"
        maxlength="80"
        placeholder="新省份/州名称"
        @keyup.enter="addProvince"
      />
      <button type="button" class="btn btn-primary btn-sm" @click="addProvince">添加省份</button>
    </div>
  </section>
</template>

<script setup>
import { ref, reactive, inject } from 'vue'

const showToast = inject('showToast', (msg) => alert(msg))

const regions = defineModel({ type: Array, default: () => [] })

const newProvince = ref('')
const newCityByProvince = reactive({})

function addProvince() {
  const name = newProvince.value.trim()
  if (!name) {
    showToast('请输入省份名称')
    return
  }
  if (regions.value.some(r => r.province.trim() === name)) {
    showToast('该省份已存在')
    return
  }
  regions.value.push({ province: name, cities: [name] })
  newProvince.value = ''
}

function removeProvince(index) {
  if (regions.value.length <= 1) return
  regions.value.splice(index, 1)
}

function addCity(pIndex) {
  const name = (newCityByProvince[pIndex] || '').trim()
  if (!name) {
    showToast('请输入城市名称')
    return
  }
  const region = regions.value[pIndex]
  if (region.cities.some(c => c.trim() === name)) {
    showToast('该城市已存在')
    return
  }
  region.cities.push(name)
  newCityByProvince[pIndex] = ''
}

function removeCity(pIndex, cIndex) {
  const region = regions.value[pIndex]
  if (region.cities.length <= 1) return
  region.cities.splice(cIndex, 1)
}

/** 导出前清洗数据 */
function getNormalized() {
  return regions.value
    .map(r => ({
      province: r.province.trim(),
      cities: r.cities.map(c => c.trim()).filter(Boolean),
    }))
    .filter(r => r.province && r.cities.length)
}

defineExpose({ getNormalized })
</script>

<style scoped>
.regions-card {
  max-width: 640px;
  padding: 24px;
  margin-bottom: 20px;
}

.section-head h3 {
  font-size: 16px;
  font-weight: 700;
  margin-bottom: 6px;
}

.section-head p {
  font-size: 13px;
  color: var(--text-muted);
  margin-bottom: 16px;
}

.regions-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-bottom: 16px;
}

.region-block {
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 14px;
  background: #fafafa;
}

.region-head {
  display: flex;
  gap: 10px;
  align-items: center;
  margin-bottom: 12px;
}

.province-input {
  flex: 1;
  font-weight: 600;
}

.cities-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 10px;
  padding-left: 8px;
}

.city-row {
  display: flex;
  gap: 10px;
  align-items: center;
}

.city-row .form-input {
  flex: 1;
}

.city-add,
.province-add {
  display: flex;
  gap: 10px;
  align-items: center;
}

.city-add {
  padding-top: 8px;
  border-top: 1px dashed var(--border);
}

.city-add .form-input,
.province-add .form-input {
  flex: 1;
}

.province-add {
  padding-top: 12px;
  border-top: 1px solid var(--border);
}
</style>
