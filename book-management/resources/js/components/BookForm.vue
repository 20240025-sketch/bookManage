<template>
  <div>
    <!-- ...other form inputs... -->
    <div class="mb-4">
      <label for="title_transcription" class="block text-gray-700 font-bold mb-2">タイトルのヨミ</label>
      <input
        id="title_transcription"
        type="text"
        v-model="localBook.title_transcription"
        class="border rounded px-3 py-2 w-full"
        placeholder="タイトルのヨミを入力してください"
      />
    </div>
    <input
      type="text"
      v-model="localBook.isbn"
      @blur="$emit('isbn-blur', localBook.isbn)"
      placeholder="ISBNコードを入力してください"
    />
    <!-- ...other form inputs... -->
  </div>
</template>

<script>
export default {
  name: 'BookForm',
  props: {
    value: Object
    // ...other props...
  },
  emits: ['update:value', 'isbn-blur'],
  data() {
    return {
      localBook: { ...this.value }
    };
  },
  watch: {
    value: {
      handler(val) {
        this.localBook = { ...val };
      },
      deep: true
    },
    localBook: {
      handler(val) {
        this.$emit('update:value', val);
      },
      deep: true
    }
  }
  // ...existing methods and lifecycle hooks...
};
</script>

<style scoped>
/* ...existing styles... */
</style>