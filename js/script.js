'use strict';

const menuButton=document.querySelector('#menu-button');
const navigation=document.querySelector('#main-navigation');
const themeButton=document.querySelector('#theme-button');
const notification=document.querySelector('#notification');
const closeNotification=document.querySelector('#close-notification');
const modal=document.querySelector('#event-modal');
const openModalButtons=document.querySelectorAll('.open-modal');
const closeModalButton=document.querySelector('#close-modal');
const faqButtons=document.querySelectorAll('.faq-question');
const slides=document.querySelectorAll('.slide');
const previousButton=document.querySelector('#previous-slide');
const nextButton=document.querySelector('#next-slide');

if(menuButton&&navigation){menuButton.addEventListener('click',()=>{const expanded=menuButton.getAttribute('aria-expanded')==='true';menuButton.setAttribute('aria-expanded',String(!expanded));navigation.classList.toggle('menu-open')})}

function applyTheme(theme){document.body.classList.toggle('dark-theme',theme==='dark');if(themeButton)themeButton.textContent=theme==='dark'?'☀️ Light Mode':'🌙 Dark Mode'}
const savedTheme=localStorage.getItem('studenthub-theme'); if(savedTheme)applyTheme(savedTheme);
if(themeButton){themeButton.addEventListener('click',()=>{const newTheme=document.body.classList.contains('dark-theme')?'light':'dark';applyTheme(newTheme);localStorage.setItem('studenthub-theme',newTheme)})}

if(closeNotification&&notification)closeNotification.addEventListener('click',()=>notification.hidden=true);

function openModal(){if(modal){modal.hidden=false;closeModalButton?.focus()}}
function closeModal(){if(modal)modal.hidden=true}
openModalButtons.forEach(b=>b.addEventListener('click',openModal));
closeModalButton?.addEventListener('click',closeModal);
modal?.addEventListener('click',e=>{if(e.target===modal)closeModal()});
document.addEventListener('keydown',e=>{if(e.key==='Escape'&&modal&&!modal.hidden)closeModal()});

faqButtons.forEach(button=>button.addEventListener('click',()=>{const answer=document.getElementById(button.getAttribute('aria-controls'));const expanded=button.getAttribute('aria-expanded')==='true';button.setAttribute('aria-expanded',String(!expanded));answer.hidden=expanded}));

let currentSlide=0;
function showSlide(index){if(!slides.length)return;if(index>=slides.length)currentSlide=0;if(index<0)currentSlide=slides.length-1;slides.forEach((slide,i)=>slide.hidden=i!==currentSlide)}
nextButton?.addEventListener('click',()=>{currentSlide++;showSlide(currentSlide)});
previousButton?.addEventListener('click',()=>{currentSlide--;showSlide(currentSlide)});
showSlide(currentSlide);
