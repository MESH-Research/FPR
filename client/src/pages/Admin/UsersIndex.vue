<template>
  <h2 class="q-pl-lg">All Users</h2>
  <query-table
    :query="GET_USERS"
    t-prefix="admin.users"
    :visible-columns
    @row-click="handleUserListBasicClick"
  />
</template>

<script setup>
import QueryTable from "src/components/tables/QueryTable.vue"
import { GET_USERS } from "src/graphql/queries"

const visibleColumns = ["name", "username", "email"]
import { useRouter } from "vue-router"

async function handleUserListBasicClick(_, row) {
  goToUserDetail(row.id)
}

const { push } = useRouter()
async function goToUserDetail(id) {
  push({
    name: "user_details",
    params: { id },
  })
}
</script>
