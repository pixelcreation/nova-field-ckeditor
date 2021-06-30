<script>
    export default {
        props: ['resource', 'resourceName', 'resourceId', 'field'],
    }
</script>
<template>
    <panel-item :field="field">
        <template slot="value">
            <div class="ck ck-editor_content">
                <excerpt
                    :content="field.value"
                    :should-show="field.shouldShow"
                    :plain-text="false"
                />
            </div>
        </template>
    </panel-item>
</template>
<style lang="sass">
    .ck-editor_content
        @import "./../../sass/field.sass"
        @import "./../../sass/figures.sass"
        @import "./../../sass/blocks.sass"
</style>

