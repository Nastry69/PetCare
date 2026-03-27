import { Routes, Route } from 'react-router-dom'
import Layout from './components/Layout'
import Home from './pages/Home'
import AnimalDetail from './pages/AnimalDetail'
import Dashboard from './pages/Dashboard'
import Animals from './pages/Animals'
import EventForm from './pages/EventForm'


function App() {
  return (
    <Layout>
      <Routes>
        {/* <Route path="/" element={<Home />} /> */}
        <Route path="/dashboard" element={<Dashboard />} />
        <Route path="/animals" element={<Animals />} />
        <Route path="/animals/:id" element={<AnimalDetail />} />
        <Route path="/events/new" element={<EventForm />} />
      </Routes>
    </Layout>
  )
}

export default App