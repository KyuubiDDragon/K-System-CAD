<script lang="ts">
import { defineComponent, h } from "vue";
// A deliberately small formatter. User HTML is always a text node, never v-html.
export default defineComponent({
  props: { text: { type: String, default: "" } },
  setup(props) {
    return () =>
      h(
        "div",
        { class: "rich-text" },
        props.text
          .split(
            /(~~[^~\n]+~~|\*\*[^*\n]+\*\*|\*[^*\n]+\*|https?:\/\/[^\s<>]+)/g,
          )
          .map((part) =>
            part.startsWith("~~")
              ? h("s", part.slice(2, -2))
              : part.startsWith("**")
                ? h("strong", part.slice(2, -2))
                : part.startsWith("*")
                  ? h("em", part.slice(1, -1))
                  : /^https?:\/\//.test(part)
                    ? h(
                        "a",
                        {
                          href: part,
                          target: "_blank",
                          rel: "noopener noreferrer",
                        },
                        part,
                      )
                    : part,
          ),
      );
  },
});
</script>
