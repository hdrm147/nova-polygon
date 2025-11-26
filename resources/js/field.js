import IndexField from './components/IndexField'
import DetailField from './components/DetailField'
import FormField from './components/FormField'
import PreviewField from './components/PreviewField'

Nova.booting((app, store) => {
  app.component('index-polygon', IndexField)
  app.component('detail-polygon', DetailField)
  app.component('form-polygon', FormField)
  // app.component('preview-polygon', PreviewField)
})
