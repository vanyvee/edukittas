<template>
    <slot v-if="allowed" />
</template>

<script setup>
const auth = useAuthStore()
const props = defineProps({
    auth: Boolean,
    type: [String, Array]

})

const allowed = computed(()=>{
    if(props.auth) return auth.isLoggedIn
    if(props.type) {
        const types = [].concat(props.type)
        return types.includes(auth.authtype)
    }
    return false
})
</script>

<!-- usage
<Can type="student">      <StudentResult /> </Can>

<Can :type="['admin', 'director']">
    <ManageUser/>
</Can> -->