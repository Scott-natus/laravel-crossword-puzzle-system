from django.urls import path
from . import views
from django.contrib.auth import views as auth_views

urlpatterns = [
    path('', views.index, name='index'),
    path('submit-consultation/', views.submit_consultation, name='submit_consultation'),
    path('admin-dashboard/', views.admin_dashboard, name='admin_dashboard'),
    path('manage/', views.manage_welcome, name='manage_welcome'),
    path('manage/hero-banner/', views.manage_hero_banner, name='manage_hero_banner'),
    path('manage/project-overview/', views.manage_project_overview, name='manage_project_overview'),
    path('manage/location-environment/', views.manage_location_environment, name='manage_location_environment'),
    path('manage/site-plan/', views.manage_site_plan, name='manage_site_plan'),
    path('manage/sales-info/', views.manage_sales_info, name='manage_sales_info'),
    path('manage/gallery/', views.manage_gallery, name='manage_gallery'),
    path('manage/consult/', views.manage_consult, name='manage_consult'),
    path('manage/consult/<int:consult_id>/', views.manage_consult_detail, name='manage_consult_detail'),
    path('manage/login/', auth_views.LoginView.as_view(template_name='manage/login.html'), name='manage_login'),
    path('manage/logout/', auth_views.LogoutView.as_view(next_page='manage_login'), name='manage_logout'),
    path('manage/password_change/', auth_views.PasswordChangeView.as_view(template_name='manage/password_change.html'), name='manage_password_change'),
] 