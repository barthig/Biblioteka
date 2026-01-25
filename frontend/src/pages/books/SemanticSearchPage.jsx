import React from 'react'
import SemanticSearch from '../../components/books/SemanticSearch'
import PageHeader from '../../components/ui/PageHeader'
import StatGrid from '../../components/ui/StatGrid'
import StatCard from '../../components/ui/StatCard'
import SectionCard from '../../components/ui/SectionCard'

export default function SemanticSearchPage() {
  return (
    <div className="page">
      <PageHeader
        title="Wyszukiwanie semantyczne"
        subtitle="Szukaj książek po znaczeniu opisu."
      />
      <StatGrid>
        <StatCard title="Tryb" value="Semantyczny" subtitle="AI wyszukiwanie" />
        <StatCard title="Wyniki" value="—" subtitle="Po wyszukaniu" />
        <StatCard title="Język" value="PL" subtitle="Zapytania" />
      </StatGrid>
      <SectionCard>
        <SemanticSearch />
      </SectionCard>
    </div>
  )
}
