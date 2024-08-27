import { ApplicationConfig, provideZoneChangeDetection } from '@angular/core';
import { provideRouter } from '@angular/router';

import { routes } from './app.routes';
import { HTTP_INTERCEPTORS, provideHttpClient } from '@angular/common/http';
import { provideClientHydration } from '@angular/platform-browser';
import { TokenExpireInterceptor } from './token-expire.interceptor';
// import { provideAnimationsAsync } from '@angular/platform-browser/animations/async';

export const appConfig: ApplicationConfig = {
  // providers: [provideZoneChangeDetection({ eventCoalescing: true }), provideRouter(routes) , provideRouter(routes),provideHttpClient(), provideAnimationsAsync(), provideAnimationsAsync()]

  providers: [provideZoneChangeDetection({ eventCoalescing: true }), 
    provideRouter(routes), provideRouter(routes),provideHttpClient(),
    provideClientHydration(),
    { provide: HTTP_INTERCEPTORS, useFactory: TokenExpireInterceptor, multi: true }
  ]
};
