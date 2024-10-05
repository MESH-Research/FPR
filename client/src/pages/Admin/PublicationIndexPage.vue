<template>
  <div>
    <h2 class="q-pl-lg">{{ $t("publication.admin_header") }}</h2>
    <q-expansion-item
      :label="$t('publication.create_button')"
      switch-toggle-side
      header-class="light-grey"
      data-cy="create_pub_button"
    >
      <CreateForm @created="publicationCreated" />
    </q-expansion-item>
    <query-table
      :query="GET_PUBLICATIONS"
      t-prefix="admin.publication"
      :columns
      @new="console.log('')"
    >
      <template #body-cell-actions="rProps">
        <q-td :props="rProps">
          <q-btn-group flat>
            <q-btn
              icon="visibility"
              class="dark-grey"
              :to="{
                name: 'publication:home',
                params: { id: rProps.value },
              }"
              :aria-label="$t('publication.view_button_aria')"
            >
              <q-tooltip :delay="500">
                {{ $t("publication.view_button") }}
              </q-tooltip>
            </q-btn>
            <q-btn-dropdown
              auto-close
              class="dark-grey"
              :aria-label="$t('publication.configure')"
            >
              <q-list>
                <q-item :to="destRoute(rProps.value, 'basic')">
                  <q-item-section avatar>
                    <q-icon class="dark-grey" name="tune" />
                  </q-item-section>
                  <q-item-section>
                    {{ $t(pageTitleKey("basic")) }}
                  </q-item-section>
                </q-item>
                <q-item :to="destRoute(rProps.value, 'users')">
                  <q-item-section avatar>
                    <q-icon class="dark-grey" name="people" />
                  </q-item-section>
                  <q-item-section>
                    {{ $t(pageTitleKey("users")) }}
                  </q-item-section>
                </q-item>
                <q-item :to="destRoute(rProps.value, 'criteria')">
                  <q-item-section avatar>
                    <q-icon class="dark-grey" name="card_membership" />
                  </q-item-section>
                  <q-item-section>
                    {{ $t(pageTitleKey("criteria")) }}
                  </q-item-section>
                </q-item>
                <q-item :to="destRoute(rProps.value, 'content')">
                  <q-item-section avatar>
                    <q-icon class="dark-grey" name="toc" />
                  </q-item-section>
                  <q-item-section>
                    {{ $t(pageTitleKey("content")) }}
                  </q-item-section>
                </q-item>
              </q-list>
              <template #label>
                <q-icon name="settings" />
                <q-tooltip :delay="500">{{
                  $t("publication.configure")
                }}</q-tooltip>
              </template>
            </q-btn-dropdown>
          </q-btn-group>
        </q-td>
      </template>
    </query-table>
  </div>
</template>

<script setup>
import QueryTable from "src/components/tables/QueryTable.vue"
import { GET_PUBLICATIONS } from "src/graphql/queries"
import CreateForm from "src/components/forms/Publication/CreateForm.vue"
import { useRouter } from "vue-router"
const destRoute = (id, page) => ({
  name: `publication:setup:${page}`,
  params: { id },
})

const columns = [
  {
    name: "name",
    field: "name",
    align: "left",
  },
  {
    name: "actions",
    field: "id",
    align: "right",
  },
]

const pageTitleKey = (page) => `publication.setup_pages.${page}`

const { push } = useRouter()
function publicationCreated(publication) {
  push({
    name: "publication:setup:basic",
    params: { id: publication.id },
  })
}
</script>
