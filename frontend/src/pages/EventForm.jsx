import { useState } from "react";

function EventForm() {
  const [form, setForm] = useState({
    animal: "",
    type: "",
    date: "",
    heure: "",
    statut: "a_confirmer",
    commentaire: "",
    rappel: false,
  });

  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;
    setForm((prev) => ({
      ...prev,
      [name]: type === "checkbox" ? checked : value,
    }));
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    console.log("Formulaire événement :", form);
  };

  return (
    <div className="mx-auto max-w-4xl space-y-8">
      <div>
        <p className="text-sm font-medium text-blue-600">Événement</p>
        <h1 className="mt-2 text-3xl font-bold">Ajouter un événement</h1>
        <p className="mt-2 text-slate-500">
          Complétez les informations pour programmer un nouvel événement.
        </p>
      </div>

      <form
        onSubmit={handleSubmit}
        className="rounded-3xl border bg-white p-6 shadow-sm sm:p-8"
      >
        <div className="grid gap-6 md:grid-cols-2">
          <div>
            <label className="mb-2 block text-sm font-medium text-slate-700">
              Animal
            </label>
            <select
              name="animal"
              value={form.animal}
              onChange={handleChange}
              className="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-blue-500"
            >
              <option value="">Sélectionner un animal</option>
              <option value="rex">Rex</option>
              <option value="milo">Milo</option>
              <option value="luna">Luna</option>
            </select>
          </div>

          <div>
            <label className="mb-2 block text-sm font-medium text-slate-700">
              Type d’événement
            </label>
            <select
              name="type"
              value={form.type}
              onChange={handleChange}
              className="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-blue-500"
            >
              <option value="">Sélectionner un type</option>
              <option value="vaccin">Vaccin</option>
              <option value="consultation">Consultation</option>
              <option value="toilettage">Toilettage</option>
            </select>
          </div>

          <div>
            <label className="mb-2 block text-sm font-medium text-slate-700">
              Date
            </label>
            <input
              type="date"
              name="date"
              value={form.date}
              onChange={handleChange}
              className="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-blue-500"
            />
          </div>

          <div>
            <label className="mb-2 block text-sm font-medium text-slate-700">
              Heure
            </label>
            <input
              type="time"
              name="heure"
              value={form.heure}
              onChange={handleChange}
              className="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-blue-500"
            />
          </div>

          <div>
            <label className="mb-2 block text-sm font-medium text-slate-700">
              Statut
            </label>
            <select
              name="statut"
              value={form.statut}
              onChange={handleChange}
              className="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-blue-500"
            >
              <option value="a_confirmer">À confirmer</option>
              <option value="prevu">Prévu</option>
              <option value="effectue">Effectué</option>
              <option value="annule">Annulé</option>
            </select>
          </div>

          <div className="flex items-end">
            <label className="flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3">
              <input
                type="checkbox"
                name="rappel"
                checked={form.rappel}
                onChange={handleChange}
                className="h-4 w-4"
              />
              <span className="text-sm font-medium text-slate-700">
                Activer le rappel
              </span>
            </label>
          </div>
        </div>

        <div className="mt-6">
          <label className="mb-2 block text-sm font-medium text-slate-700">
            Commentaire
          </label>
          <textarea
            name="commentaire"
            value={form.commentaire}
            onChange={handleChange}
            rows="5"
            className="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-blue-500"
            placeholder="Ajouter une note ou une précision..."
          />
        </div>

        <div className="mt-8 flex flex-wrap gap-3">
          <button
            type="submit"
            className="rounded-2xl bg-blue-600 px-6 py-3 font-medium text-white shadow hover:bg-blue-700"
          >
            Enregistrer
          </button>

          <button
            type="button"
            className="rounded-2xl border border-slate-200 px-6 py-3 font-medium text-slate-700 hover:bg-slate-50"
          >
            Annuler
          </button>
        </div>
      </form>
    </div>
  );
}

export default EventForm;