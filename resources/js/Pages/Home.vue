<template>
  <div class="page-container home-page">
    <inertia-head title="Mismatch Finder" />
    <section id="description-section">
      <header class="description-header">
        <h2 class="h4">
          {{ $i18n('about-mismatch-finder-title') }}
        </h2>
        <cdx-button
          id="faq-button"
          weight="quiet"
          action="progressive"
          @click="faqDialog = true"
        >
          <cdx-icon :icon="cdxIconInfo" />
          <span class="text-with-icon-button">{{ $i18n('faq-button') }}</span>
        </cdx-button>
      </header>
      <cdx-dialog
        id="faq-dialog"
        v-model:open="faqDialog"
        :title="$i18n('faq-dialog-title')"
        :primary-action="{
          label: $i18n('confirm-dialog-button'),
          namespace: 'faq-confirm',
          actionType: 'progressive'
        }"
        @primary="() => faqDialog = false"
        close-button-label="X"
      >
        <section>
          <h3 class="h5">
            {{ $i18n('faq-dialog-question-finding-mismatches' ) }}
          </h3>
          <p>{{ $i18n('faq-dialog-answer-finding-mismatches') }}</p>
          <ul>
            <li>{{ $i18n('faq-dialog-answer-finding-mismatches-sources-1') }}</li>
            <li>{{ $i18n('faq-dialog-answer-finding-mismatches-sources-2') }}</li>
            <li>{{ $i18n('faq-dialog-answer-finding-mismatches-sources-3') }}</li>
          </ul>
        </section>
        <section>
          <h3 class="h5">
            {{ $i18n('faq-dialog-question-relevance') }}
          </h3>
          <p>{{ $i18n('faq-dialog-answer-relevance') }}</p>
        </section>
        <section>
          <h3 class="h5">
            {{ $i18n('faq-dialog-question-contributing') }}
          </h3>
          <p
            v-i18n-html:faq-dialog-answer-contributing="[
              'https://phabricator.wikimedia.org/'
            ]"
          />
        </section>
        <section>
          <h3 class="h5">
            {{ $i18n('faq-dialog-question-more-info') }}
          </h3>
          <p
            v-i18n-html:faq-dialog-answer-more-info="[
              'https://github.com/wmde/wikidata-mismatch-finder',
              'https://www.wikidata.org/wiki/Wikidata:Mismatch_Finder',
              'https://www.wikidata.org/wiki/Wikidata_talk:Mismatch_Finder'
            ]"
          />
        </section>
      </cdx-dialog>
      <p id="about-description">
        {{ $i18n('about-mismatch-finder-description') }}
      </p>
    </section>

    <section id="message-section">
      <cdx-message
        v-if="unexpectedError || serversideValidationError"
        type="error"
      >
        <span>{{ $i18n('server-error') }}</span>
      </cdx-message>
    </section>

    <section id="querying-section">
      <div class="heading">
        <h2 class="h5">
          {{ $i18n('item-form-title') }}
        </h2>
        <cdx-button
          class="random-mismatches"
          weight="normal"
          @click="showRandom()"
          :disabled="loading"
        >
          <cdx-icon :icon="cdxIconDie" />
          <span class="text-with-icon-button">{{ $i18n('random-mismatches') }}</span>
        </cdx-button>
      </div>
      <form
        id="items-form"
        @submit.prevent="send"
      >
        <item-id-search-textarea 
          :loading="loading"
          ref="textarea"
        />
        <div class="form-buttons">
          <cdx-button
            class="submit-ids"
            weight="primary"
            action="progressive"
            native-type="submit"
            :disabled="loading"
          >
            {{ $i18n('item-form-submit') }}
          </cdx-button>
        </div>
      </form>
    </section>
  </div>
</template>

<script setup lang="ts">
import { Head as InertiaHead, usePage } from '@inertiajs/inertia-vue3';
import { useStore } from '../store';
import { CdxDialog, CdxButton, CdxIcon, CdxMessage } from "@wikimedia/codex";
import { cdxIconDie, cdxIconInfo } from '@wikimedia/codex-icons';
import ItemIdSearchTextarea from '../Components/ItemIdSearchTextarea.vue';
import { ref, computed } from 'vue';
import type { Ref } from 'vue';
import { Inertia } from '@inertiajs/inertia';

interface ErrorMessages {
    [ key : string ] : string
}

interface FlashMessages {
    errors : { [ key : string ] : string }
}

const textarea = ref<InstanceType<typeof ItemIdSearchTextarea> | null>(null);
const store = useStore();
const page = usePage();
const textareaInputValue: Ref<string> = ref(store.lastSearchedIds);
const faqDialog = ref(false);

const serversideValidationError = computed<boolean>(() => {
    const errors = page.props.value.errors as ErrorMessages;
    return errors && Object.keys(errors).length > 0;
});

const unexpectedError = computed<string>(() => {
    const flashMessages = page.props.value.flash as FlashMessages;
    return (flashMessages.errors && flashMessages.errors.unexpected);
});

const loading = computed<boolean>(() => {
    return (store.loading);
});

function send(): void{
    (textarea as InstanceType<typeof ItemIdSearchTextarea>).value.validate();

    if((textarea as InstanceType<typeof ItemIdSearchTextarea>).value.validationError) {
        return;
    }
    store.saveSearchedIds( textareaInputValue.value );
    Inertia.get( '/results', 
        { ids: (textarea as InstanceType<typeof ItemIdSearchTextarea>).value.serializeInput() }
    );
}

function showRandom(): void{
    Inertia.get( '/random' );
}
</script>

<style lang="scss">
@import "@wikimedia/codex-design-tokens/theme-wikimedia-ui";
@import '../../sass/typography';
@import '../../css/custom-variables.css';

#about-description {
    @include body-m;
}

#querying-section .heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: var(--dimension-spacing-small);

    h2 {
        // the previous section already has enough margin-bottom
        margin-top: 0;
    }
}

#items-form {
    /**
    * Colors
    */
    background-color: $background-color-interactive-subtle;

    /**
    * Border
    */
    border-style: $border-style-base;
    border-width: $border-width-base;
    border-color: $border-color-subtle;
    border-radius: $border-radius-base;

    /**
    * Layout
    */
    padding: var(--dimension-spacing-large);
    margin: var(--dimension-layout-xsmall) 0;

    // Any direct decendent of this form that has a predecessor element will
    // get a top margin, this creates the even gutter between elements or "stack"
    // See https://every-layout.dev/layouts/stack/#the-solution
    & > * + * {
        margin-top: var(--dimension-spacing-large);
    }

    .form-buttons {
        text-align: end;
    }
}
</style>
