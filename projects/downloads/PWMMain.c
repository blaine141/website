#include <16F876A.h>
#device ADC=10
#use delay(clock=16384000)
#fuses HS,NOWDT,NOPROTECT,NOLVP,BROWNOUT,NOWRT,PUT,NOCPD,DEBUG

//Defines which pins mean what
#define OUT_PIN			PIN_C5
#define SS_LEFT			PIN_C7
#define SS_RIGHT		PIN_C6
#define SS_TOP			PIN_C1
#define SS_TL			PIN_A5
#define SS_TR			PIN_C0
#define SS_CENTER		PIN_B5
#define SS_BL			PIN_A3
#define SS_BR			PIN_A2
#define SS_BOTTOM		PIN_A1
#define SS_NEGATIVE		PIN_C4
#define POWER_PIN		PIN_B0

//Variables
long length = 1000;		//Length of pulse in us
int8 percentLeft = 'H';	//10s place of the pecent
int8 percentRight = 'i';	//1s place of percent


//////////////////////////////////
//	Outputs digit to Seven-seg.	//
// 	Digit ' ' clears the display//
//	Dot in bottom right corner	//
//	if negative.				//
//////////////////////////////////
void display(int8 digit)
{
	if(digit == 0)
	{
		Output_high(SS_TOP);
		Output_high(SS_TL);
		Output_high(SS_TR);
		Output_low(SS_CENTER);
		Output_high(SS_BL);
		Output_high(SS_BR);
		Output_high(SS_BOTTOM);
	}	
	if(digit == 1)
	{
		Output_low(SS_TOP);
		Output_low(SS_TL);
		Output_high(SS_TR);
		Output_low(SS_CENTER);
		Output_low(SS_BL);
		Output_high(SS_BR);
		Output_low(SS_BOTTOM);
	}
	if(digit == 2)
	{
		Output_high(SS_TOP);
		Output_low(SS_TL);
		Output_high(SS_TR);
		Output_high(SS_CENTER);
		Output_high(SS_BL);
		Output_low(SS_BR);
		Output_high(SS_BOTTOM);
	}
	if(digit == 3)
	{
		Output_high(SS_TOP);
		Output_low(SS_TL);
		Output_high(SS_TR);
		Output_high(SS_CENTER);
		Output_low(SS_BL);
		Output_high(SS_BR);
		Output_high(SS_BOTTOM);
	}
	if(digit == 4)
	{
		Output_low(SS_TOP);
		Output_high(SS_TL);
		Output_high(SS_TR);
		Output_high(SS_CENTER);
		Output_low(SS_BL);
		Output_high(SS_BR);
		Output_low(SS_BOTTOM);
	}
	if(digit == 5)
	{
		Output_high(SS_TOP);
		Output_high(SS_TL);
		Output_low(SS_TR);
		Output_high(SS_CENTER);
		Output_low(SS_BL);
		Output_high(SS_BR);
		Output_high(SS_BOTTOM);
	}
	if(digit == 6)
	{
		Output_high(SS_TOP);
		Output_high(SS_TL);
		Output_low(SS_TR);
		Output_high(SS_CENTER);
		Output_high(SS_BL);
		Output_high(SS_BR);
		Output_high(SS_BOTTOM);
	}
	if(digit == 7)
	{
		Output_high(SS_TOP);
		Output_low(SS_TL);
		Output_high(SS_TR);
		Output_low(SS_CENTER);
		Output_low(SS_BL);
		Output_high(SS_BR);
		Output_low(SS_BOTTOM);
	}
	if(digit == 8)
	{
		Output_high(SS_TOP);
		Output_high(SS_TL);
		Output_high(SS_TR);
		Output_high(SS_CENTER);
		Output_high(SS_BL);
		Output_high(SS_BR);
		Output_high(SS_BOTTOM);
	}	
	if(digit == 9)
	{
		Output_high(SS_TOP);
		Output_high(SS_TL);
		Output_high(SS_TR);
		Output_high(SS_CENTER);
		Output_low(SS_BL);
		Output_high(SS_BR);
		Output_high(SS_BOTTOM);
	}
	if(digit == 'H')
	{
		Output_low(SS_TOP);
		Output_high(SS_TL);
		Output_high(SS_TR);
		Output_high(SS_CENTER);
		Output_high(SS_BL);
		Output_high(SS_BR);
		Output_low(SS_BOTTOM);
	}
	if(digit == 'i')
	{
		Output_low(SS_TOP);
		Output_low(SS_TL);
		Output_low(SS_TR);
		Output_low(SS_CENTER);
		Output_high(SS_BL);
		Output_low(SS_BR);
		Output_low(SS_BOTTOM);
	}
	if(digit == ' ')
	{
		Output_low(SS_TOP);
		Output_low(SS_TL);
		Output_low(SS_TR);
		Output_low(SS_CENTER);
		Output_low(SS_BL);
		Output_low(SS_BR);
		Output_low(SS_BOTTOM);
	}
	if(length<=495)		//If direction is negative.
		output_high(SS_NEGATIVE);
	else
		output_low(SS_NEGATIVE);
}

//////////////////////////////////
//	Gets the value from the pot	//
// 	and calculates pulse and	//
//	percent						//
//////////////////////////////////
void getFromPot()					
{
	length = read_adc(); 				//Get value from pot. 0-1023
	
	if (500<=length&&length<524)			//If close to the center, make it 0
		length = 524;
		
	if(length>512)						//If positive, calculate the percent
	{
		length -= 24;
		percentLeft=(length-500)/50;
		percentRight=(length-500)/5%10;
	}
	else								//If negative, calculate the percent
	{
		percentLeft=(500-length)/50;
		percentRight=(500-length)/5%10;
	}
	if(percentLeft==10)					//If 100%, display 99
	{
		percentLeft=9;
		percentRight=9;
	}
}		

//////////////////////////////////
//	Main method					//
//////////////////////////////////
void Main()
{
	//Init ports and A to D
	set_tris_a(1);
	set_tris_b(1);
	set_tris_c(0);
	setup_adc(ADC_CLOCK_INTERNAL );
	setup_adc_ports(AN0);
	set_adc_channel(0);
	
	for(long i=0;i<50;i++) 	//Display Hi
	{ 	
		output_high(OUT_PIN);	//Code to output neutral through PWM while displaing Hi
		long delay = 1500;			//Add the default 1ms pulse
		while(delay>254)			//Delay the length of the pulse
		{
			delay_us(255);			//Not 255 to account for time the subraction and while loop take
			delay-=254;
		}	
		delay_us(delay);
		output_low(OUT_PIN);
		
		
		output_low(SS_RIGHT);		//Set left digit active
		if(input_state(POWER_PIN))	//If the switch is not made, don't display to conserve power. Will give more time to output while shutting down.
			output_high(SS_LEFT);
		display(percentLeft);		//Display left digit
		delay_ms(5);				//Delay to keep number on screen
		
		if(input_state(POWER_PIN))	//If the switch is not made, don't display to conserve power. Will give more time to output while shutting down.
			output_high(SS_RIGHT);		//Set right digit active
		output_low(SS_LEFT);		
		display(percentRight);		//Display right digit
		delay_ms(5);				//Delay to keep number on screen
		
		output_low(SS_RIGHT);		//Set left digit active
		if(input_state(POWER_PIN))	//If the switch is not made, don't display to conserve power. Will give more time to output while shutting down.
			output_high(SS_LEFT);
		display(percentLeft);		//Display left digit
		delay_ms(5);				//Delay to keep number on screen
		
		if(input_state(POWER_PIN))	//If the switch is not made, don't display to conserve power. Will give more time to output while shutting down.
			output_high(SS_RIGHT);		//Set right digit active
		output_low(SS_LEFT);		
		display(percentRight);		//Display right digit
		delay_ms(4);				//Delay to keep number on screen
		
		if(!input_state(POWER_PIN))	//If the switch is not made, don't display and output 0
		{
			output_low(SS_RIGHT);
			output_low(SS_LEFT);
			output_high(OUT_PIN);	//Code to output neutral through PWM while blinking 0
			long delay = 1500;			//Add the default 1ms pulse
			while(delay>254)			//Delay the length of the pulse
			{
				delay_us(255);			//Not 255 to account for time the subraction and while loop take
				delay-=254;
			}	
			delay_us(delay);
			output_low(OUT_PIN);
			while(!input_state(POWER_PIN))	//Do nothing until powerdown unless the device is powered again
			{
			}	
		}	
	}	
	output_low(SS_RIGHT);	//Shut off screen.
	int blinkCounter = 0;	//Variable used to record time passed to blink the digit 0
	while(length<=495||length>=505)	//If not set to 0, requires the user to set to 0 to start use.
	{
		blinkCounter++;		//Increment blink counter
		read_adc(ADC_START_ONLY);	//Start reading from Pot
		
		output_high(OUT_PIN);	//Code to output neutral through PWM while blinking 0
		long delay = 1500;			//Add the default 1ms pulse
		while(delay>254)			//Delay the length of the pulse
		{
			delay_us(255);			//Not 255 to account for time the subraction and while loop take
			delay-=254;
		}	
		delay_us(delay);
		output_low(OUT_PIN);
		
		output_low(SS_RIGHT);		//Set left digit active
		delay_ms(5);				//Delay to keep number on screen
		
		if(input_state(POWER_PIN))	//If the switch is not made, don't display to conserve power. Will give more time to output while shutting down.
			output_high(SS_RIGHT);		//Set right digit active		
		display(percentRight);		//Display right digit
		delay_ms(5);				//Delay to keep number on screen
		
		output_low(SS_RIGHT);		//Set left digit active
		delay_ms(5);				//Delay to keep number on screen
		
		if(input_state(POWER_PIN))	//If the switch is not made, don't display to conserve power. Will give more time to output while shutting down.
			output_high(SS_RIGHT);		//Set right digit active		
		display(percentRight);		//Display right digit
		delay_ms(5);				//Delay to keep number on screen
		
		if(blinkCounter>50)			//If blink conter gets to 50, reset the blinking timer.
			blinkCounter = 0;	
		if(!input_state(POWER_PIN))	//If the switch is not made, don't display and output 0
		{
			output_low(SS_RIGHT);
			output_low(SS_LEFT);
			output_high(OUT_PIN);	//Code to output neutral through PWM while blinking 0
			long delay = 1500;			//Add the default 1ms pulse
			while(delay>254)			//Delay the length of the pulse
			{
				delay_us(255);			//Not 255 to account for time the subraction and while loop take
				delay-=254;
			}	
			delay_us(delay);
			output_low(OUT_PIN);
			while(!input_state(POWER_PIN))	//Do nothing until powerdown unless the device is powered again
			{
			}	
		}	
		getFromPot();				//Get the pot value	
		if(blinkCounter>25)			//If blink timer is less than 25, display nothing. If more, display 0.
			percentRight=0;
		else
			percentRight=' ';
		
	}	
	
	
	while(true) //Main loop
	{
		output_high(OUT_PIN); 		//Start pulse
		long delay = length+1000;				//Add the default 1ms pulse
		while(delay>254)			//Delay the length of the pulse
		{
			delay_us(255);			//Not 255 to account for time the subraction and while loop take
			delay-=254;
		}	
		delay_us(delay);
		output_low(OUT_PIN);		//End pulse
		
		
		read_adc(ADC_START_ONLY);	//Start reading from Pot
		delay_ms(4);				//Delay to keep number on screen
		
		output_low(SS_RIGHT);		//Set left digit active
		if(input_state(POWER_PIN))	//If the switch is not made, don't display to conserve power. Will give more time to output while shutting down.
			output_high(SS_LEFT);
		display(percentLeft);		//Display left digit
		delay_ms(5);				//Delay to keep number on screen
		
		if(input_state(POWER_PIN))	//If the switch is not made, don't display to conserve power. Will give more time to output while shutting down.
			output_high(SS_RIGHT);		//Set right digit active
		output_low(SS_LEFT);		
		display(percentRight);		//Display right digit
		delay_ms(5);				//Delay to keep number on screen
		
		output_low(SS_RIGHT);		//Set left digit active
		if(input_state(POWER_PIN))	//If the switch is not made, don't display to conserve power. Will give more time to output while shutting down.
			output_high(SS_LEFT);
		display(percentLeft);		//Display left digit
		delay_ms(5);				//Delay to keep number on screen
		
		if(input_state(POWER_PIN))	//If the switch is not made, don't display to conserve power. Will give more time to output while shutting down.
			output_high(SS_RIGHT);		//Set right digit active
		output_low(SS_LEFT);		
		display(percentRight);		//Display right digit
		
		if(input_state(POWER_PIN))	//If switch is still made, get the value from the pot
		{
			getFromPot();	
		} 
		if(!input_state(POWER_PIN))	//If the switch is not made, don't display and output 0
		{
			output_low(SS_RIGHT);
			output_low(SS_LEFT);
			output_high(OUT_PIN);	//Code to output neutral through PWM while blinking 0
			long delay = 1500;			//Add the default 1ms pulse
			while(delay>254)			//Delay the length of the pulse
			{
				delay_us(255);			//Not 255 to account for time the subraction and while loop take
				delay-=254;
			}	
			delay_us(delay);
			output_low(OUT_PIN);
			while(!input_state(POWER_PIN))	//Do nothing until powerdown unless the device is powered again
			{
			}	
		}	
	}	
}	
