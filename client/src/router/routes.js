const routes = [
  {
    path: "/",
    component: () => import("layouts/PublicLayout.vue"),
    children: [
      { path: "", component: () => import("pages/Index.vue") },
      { path: "register", component: () => import("pages/Register.vue") },
      { path: "login", component: () => import("pages/Login.vue") },
    ],
  },
  {
    path: "/",
    component: () => import("layouts/MainLayout.vue"),
    meta: { requiresAuth: true },
    children: [
      {
        path: "verify-email/:expires/:token",
        component: () => import("pages/VerifyEmail.vue"),
      },
      {
        path: "dashboard/",
        component: () => import("pages/Dashboard.vue"),
      },
      {
        path: "account/",
        component: () => import("pages/Account/AccountLayout.vue"),
        children: [
          {
            path: "profile",
            component: () => import("pages/Account/Profile.vue"),
          },
        ],
      },
      {
        path: "feed/",
        component: () => import("pages/Feed.vue"),
      },
      {
        path: "/admin/users",
        component: () => import("pages/Admin/UsersIndex.vue"),
        meta: { requiresRoles: ["Application Administrator"] },
      },
      {
        name: "user_details",
        path: "/admin/user/:id",
        component: () => import("pages/Admin/UserDetails.vue"),
      },
      {
        path: "/admin/publications",
        component: () => import("src/pages/Admin/Publications.vue"),
        meta: {
          requiresRoles: ["Application Administrator"],
        },
      },
      {
        path: "/publications",
        component: () => import("src/pages/Publications.vue"),
      },
      {
        name: "publication_details",
        path: "/publication/:id",
        component: () => import("pages/Admin/PublicationDetails.vue"),
        meta: {
          requiresRoles: [
            "Application Administrator",
            "Publication Administrator",
            "Editor",
          ],
        },
        props: true,
      },
      {
        path: "/submissions",
        component: () => import("src/pages/Submissions.vue"),
      },
      {
        name: "submission_details",
        path: "/submission/:id",
        component: () => import("src/pages/SubmissionDetails.vue"),
        meta: {
          requiresSubmissionAccess: true,
        },
        props: true,
      },
    ],
  },
  {
    path: "/error403",
    name: "error403",
    component: () => import("pages/Error403.vue"),
  },
]

// Always leave this as last one

routes.push({
  path: "*",
  component: () => import("pages/Error404.vue"),
})

export default routes
