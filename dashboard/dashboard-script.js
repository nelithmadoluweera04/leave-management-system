document.addEventListener("DOMContentLoaded", () => {
  const interactiveCards = document.querySelectorAll('.stat-card, .data-card');

  interactiveCards.forEach(card => {
    card.style.transition = "transform 0.3s cubic-bezier(0.25, 0.8, 0.25, 1), box-shadow 0.3s ease";
    card.style.cursor = "pointer";

    card.addEventListener('mouseenter', () => {
      card.style.transform = "translateY(-6px) scale(1.02)";
      card.style.boxShadow = "0 12px 20px -5px rgba(79, 70, 229, 0.15), 0 8px 16px -8px rgba(0, 0, 0, 0.08)";
    });

    card.addEventListener('mouseleave', () => {
      card.style.transform = "translateY(0) scale(1)";
      card.style.boxShadow = "0 4px 6px -1px rgba(0, 0, 0, 0.05)";
    });
  });
});
