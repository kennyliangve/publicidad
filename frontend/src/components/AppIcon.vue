<template>
  <component
    :is="iconComponent"
    :size="size"
    :stroke-width="strokeWidth"
    :class="['app-icon', $attrs.class]"
  />
</template>

<script setup>
import { computed } from 'vue'
import {
  Home, Search, Plus, User, Briefcase, Store, Building2, Car, CarFront,
  Package, PawPrint, Users, MapPin, Phone, PenLine, Inbox, AlertTriangle,
  FileText,   ChevronRight, Clock, Sparkles, Wrench, Truck, GraduationCap,
  Heart, Calendar, Circle, SearchX, Mail, Lock, Menu,
  ArrowLeft, Copy, Share2, Eye, X, ChevronLeft, MessageCircle, Star, Check,
} from 'lucide-vue-next'

const iconMap = {
  home: Home,
  search: Search,
  plus: Plus,
  user: User,
  briefcase: Briefcase,
  store: Store,
  'building-2': Building2,
  car: Car,
  'car-front': CarFront,
  package: Package,
  'paw-print': PawPrint,
  users: Users,
  'map-pin': MapPin,
  phone: Phone,
  'pen-line': PenLine,
  inbox: Inbox,
  'alert-triangle': AlertTriangle,
  'file-text': FileText,
  'chevron-right': ChevronRight,
  clock: Clock,
  sparkles: Sparkles,
  wrench: Wrench,
  truck: Truck,
  'graduation-cap': GraduationCap,
  heart: Heart,
  calendar: Calendar,
  'search-x': SearchX,
  mail: Mail,
  lock: Lock,
  menu: Menu,
  circle: Circle,
  'arrow-left': ArrowLeft,
  copy: Copy,
  'share-2': Share2,
  eye: Eye,
  x: X,
  'chevron-left': ChevronLeft,
  'message-circle': MessageCircle,
  star: Star,
  check: Check,
}

const props = defineProps({
  name: { type: String, required: true },
  size: { type: [Number, String], default: 20 },
  strokeWidth: { type: [Number, String], default: 2 },
})

defineOptions({ inheritAttrs: false })

const iconComponent = computed(() => iconMap[props.name] || Circle)
</script>

<style scoped>
.app-icon {
  display: inline-block;
  vertical-align: middle;
  flex-shrink: 0;
}
</style>
