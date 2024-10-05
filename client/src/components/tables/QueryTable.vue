<template>
  <q-table
    v-bind="tableProps"
    v-model:pagination="paginationModel"
    flat
    table-class="q-table--bordered"
    table-header-class="bg-accent text-white"
    :rows="rows"
    :columns="tColumns"
    :loading="loading"
    :rows-per-page-options
    @request="onRequest"
  >
    <template #top="scope">
      <div class="col">
        <div class="row q-gutter-sm">
          <slot name="top-before" v-bind="scope" />
          <q-input
            v-if="searchable"
            v-model="filter"
            class="col"
            :dense="props.dense"
            debounce="300"
            placeholder="Search"
            clearable
            :bottom-slots="false"
          >
            <template #prepend>
              <q-icon name="search" />
            </template>
          </q-input>

          <slot name="top-extra" v-bind="scope" />
          <q-btn
            v-if="props.newTo"
            color="primary"
            :dense="props.dense"
            :to="newTo"
          >
            {{ t("btn.create") }}
          </q-btn>
          <q-btn
            v-else-if="props.onNew"
            :dense="props.dense"
            color="primary"
            @click="$emit('new')"
          >
            {{ t("btn.create") }}
          </q-btn>
          <q-btn icon="refresh" :dense="props.dense" @click="refetch()" />
          <slot name="top-after" v-bind="scope" />
        </div>
      </div>
    </template>
    <template #header-cell="scope">
      <q-th :props="scope">
        {{ t(`headers.${scope.col.name}`) }}
      </q-th>
    </template>
    <template v-for="(_, slotName) in slots" #[slotName]="data">
      <slot :name="slotName" v-bind="data"></slot>
    </template>
    <template
      v-for="column in compColumns"
      #[`body-cell-${column.name}`]="scope"
      :key="`body-cell-${column.name}`"
    >
      <component
        :is="getComponent(column.component)"
        v-if="column.component"
        :scope="scope"
        :dense="props.dense"
      />
    </template>
  </q-table>
</template>

<script setup>
import { omit } from "lodash"
import {
  computed,
  ref,
  useSlots,
  onMounted,
  watch,
  defineAsyncComponent,
} from "vue"
import { useQuery } from "@vue/apollo-composable"
import { useI18nPrefix } from "src/use/useI18nPrefix"

const props = defineProps({
  query: {
    type: Object,
    required: true,
  },
  field: {
    type: String,
    required: false,
    default: "",
  },
  newTo: {
    type: Object,
    required: false,
    default: undefined,
  },
  columns: {
    type: Array,
    required: false,
    default: undefined,
  },
  tPrefix: {
    type: String,
    required: false,
    default: "",
  },
  onNew: {
    type: Function,
    required: false,
    default: undefined,
  },
})

const tableProps = computed(() =>
  omit(props, [
    "query",
    "field",
    "variables",
    "newTo",
    "columns",
    "tPrefix",
    "onNew",
    "searchable",
    "timeRange",
  ]),
)

const queryVariables = computed(() =>
  props?.query?.definitions
    ?.find((p) => p.kind == "OperationDefinition")
    ?.variableDefinitions.map((d) => d.variable.name.value),
)
const searchable = computed(() => queryVariables.value.includes("search"))

const rowsPerPageOptions = computed(() =>
  queryVariables.value.includes("first") ? [10, 25, 50, 100] : [],
)

const enablePagination = computed(() => queryVariables.value.includes("page"))

const paginationModel = computed({
  get: () => (enablePagination.value ? pagination.value : undefined),
  set: (value) =>
    value && enablePagination.value ? (pagination.value = value) : undefined,
})

defineEmits(["new"])

const { provide, t } = useI18nPrefix()

const slots = useSlots()
if (props.tPrefix) {
  provide(props.tPrefix)
}

const pagination = ref({
  sortBy: "",
  descending: false,
  page: 1,
  rowsNumber: 0,
})

const filter = ref("")

const variables = computed(() => {
  return {
    ...(pagination.value.sortBy
      ? {
          orderBy: [
            {
              column: pagination.value.sortBy.toUpperCase(),
              order: pagination.value.descending ? "DESC" : "ASC",
            },
          ],
        }
      : {}),
    ...props.variables,
    first: pagination.value?.rowsPerPage,
    page: pagination.value?.page,
    search: filter.value,
  }
})

const {
  result,
  refetch,
  loading: queryLoading,
} = useQuery(props.query, variables)

const loading = ref(true)

watch(queryLoading, (newValue) => (loading.value = newValue))

onMounted(() => {
  if (!loading.value) {
    refetch()
  }
})

watch(result, (newValue) => {
  if (newValue) {
    pagination.value.rowsNumber = getField(newValue)?.paginatorInfo.total ?? 0
    pagination.value.page = getField(newValue)?.paginatorInfo.currentPage ?? 1
    pagination.value.rowsPerPage =
      getField(newValue)?.paginatorInfo.perPage ?? 25
  }
})

const onRequest = (props) => {
  if (!props.pagination) {
    return
  }
  const { page, rowsPerPage, sortBy, descending } = props.pagination

  pagination.value.page = page ?? 1
  pagination.value.rowsPerPage = rowsPerPage ?? 25
  pagination.value.sortBy = sortBy
  pagination.value.descending = descending ?? false
}
const rows = computed(() => getField(result.value)?.data ?? [])

function getField(result) {
  if (!result) {
    return null
  }
  if (!props.field) {
    return result[Object.keys(result)[0]]
  }

  return props.field.split(".").reduce((o, i) => o[i], result)
}
const tColumns = computed(() => {
  if (props.columns === undefined) {
    return undefined
  }
  if (!props.columns?.length) {
    return []
  }
  return props.columns
})

const compColumns = computed(() => {
  if (!props.columns?.length) {
    return []
  }

  return props.columns.filter((c) => c.component)
})

function getComponent(component) {
  return typeof component === "string"
    ? defineAsyncComponent(() => import(`./rows/${component}Row.vue`))
    : component
}
defineExpose({
  refetch,
  rows,
})
</script>

<style scoped></style>
