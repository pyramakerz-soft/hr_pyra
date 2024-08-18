import { ComponentFixture, TestBed } from '@angular/core/testing';

import { BoundersPopUpComponent } from './bounders-pop-up.component';

describe('BoundersPopUpComponent', () => {
  let component: BoundersPopUpComponent;
  let fixture: ComponentFixture<BoundersPopUpComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [BoundersPopUpComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(BoundersPopUpComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
